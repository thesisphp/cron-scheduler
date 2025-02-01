<?php

declare(strict_types=1);

namespace Thesis\Cron;

use Amp\Cancellation;
use Amp\CancelledException;
use Amp\DeferredCancellation;
use Revolt\EventLoop;

/**
 * @api
 * @phpstan-import-type Execution from Task
 */
final class Scheduler
{
    private const SCHEDULER_STATE_PENDING = 0;
    private const SCHEDULER_STATE_RUN = 1;

    /** @var self::SCHEDULER_* */
    private int $state = self::SCHEDULER_STATE_PENDING;

    private readonly Parser $parser;

    private Runner $runner;

    private ?DeferredCancellation $cancellation;

    /** @var list<array{Time, Task}> */
    private array $tasks = [];

    public function __construct(?Parser $parser = null)
    {
        $this->parser = $parser ?: Parser::standard();
        $this->runner = new ConcurrentRunner();
        $this->cancellation = new DeferredCancellation();
    }

    public function onRunner(Runner $runner): self
    {
        $scheduler = clone $this;
        $scheduler->runner = $runner;

        return $scheduler;
    }

    /**
     * @param non-empty-string $cron
     * @param Execution|Task $task
     * @throws ParserException
     */
    public function schedule(string $cron, callable|Task $task): self
    {
        $time = $this->parser->parse($cron);

        $task = \is_callable($task) ? new Task($task) : $task;
        if ($task->runner === null) {
            $task = $task->onRunner($this->runner);
        }

        $scheduler = clone $this;
        $scheduler->tasks[] = [$time, $task];

        return $scheduler;
    }

    public function run(
        \DateTimeImmutable $time = new \DateTimeImmutable(timezone: new \DateTimeZone('UTC')),
        ?Cancellation $cancellation = null,
    ): void {
        if ($this->state !== self::SCHEDULER_STATE_PENDING) {
            return;
        }

        $this->cancellation ??= new DeferredCancellation();
        $cancellation?->subscribe($this->stop(...));

        foreach ($this->tasks as [$schedule, $task]) {
            EventLoop::queue(function () use ($schedule, $task, $time): void {
                $cancellation = $this->cancellation?->getCancellation();

                $epoch = 0;
                foreach ($schedule->iterator($time) as $tick) {
                    $suspension = EventLoop::getSuspension();
                    $timerId = EventLoop::delay($tick->getTimestamp() - $time->getTimestamp(), $suspension->resume(...));
                    $cancellationId = $cancellation?->subscribe($suspension->throw(...));

                    if (!$task->reference) {
                        EventLoop::unreference($timerId);
                    }

                    try {
                        $suspension->suspend();
                        $task->execute($tick, ++$epoch);
                    } catch (CancelledException) {
                        return;
                    } finally {
                        EventLoop::cancel($timerId);

                        if ($cancellationId !== null) {
                            $cancellation?->unsubscribe($cancellationId);
                        }
                    }

                    if ($task->times !== -1 && $task->times <= $epoch) {
                        return;
                    }

                    $time = $tick;
                }
            });
        }

        $this->state = self::SCHEDULER_STATE_RUN;
    }

    public function stop(): void
    {
        $this->cancellation?->cancel();
        $this->cancellation = null;
        $this->state = self::SCHEDULER_STATE_PENDING;
    }
}
