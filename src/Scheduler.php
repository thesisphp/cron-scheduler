<?php

declare(strict_types=1);

namespace Thesis\Cron;

use Amp\Cancellation;
use Amp\DeferredCancellation;
use Revolt\EventLoop;

/**
 * @api
 * @phpstan-import-type Execution from Run
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

    /** @var list<Internal\Task> */
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
     * @param Execution|Run $run
     * @throws ParserException
     */
    public function schedule(string $cron, callable|Run $run): self
    {
        $schedule = $this->parser->parse($cron);

        if (\is_callable($run)) {
            $run = new Run($run);
        }

        $scheduler = clone $this;
        $scheduler->tasks[] = new Internal\Task(
            execution: $run->execution(),
            schedule: $schedule,
            times: $run->times,
            reference: $run->reference,
            runner: $run->runner ?: $this->runner,
        );

        return $scheduler;
    }

    public function run(
        \DateTimeImmutable $time = new \DateTimeImmutable(timezone: new \DateTimeZone('UTC')),
        ?Cancellation $cancellation = null,
    ): void {
        if ($this->state !== self::SCHEDULER_STATE_PENDING || \count($this->tasks) === 0) {
            return;
        }

        $this->cancellation ??= new DeferredCancellation();
        $cancellation?->subscribe($this->stop(...));

        foreach ($this->tasks as $task) {
            EventLoop::queue($task->run(...), $time, $this->cancellation->getCancellation());
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
