<?php

declare(strict_types=1);

namespace Thesis\Cron\Internal;

use Amp\Cancellation;
use Amp\CancelledException;
use Revolt\EventLoop;
use Thesis\Cron\Run;
use Thesis\Cron\Runner;
use Thesis\Cron\Time;

/**
 * @internal
 * @phpstan-import-type Execution from Run
 */
final class Task
{
    /** @var Execution */
    private $execution;

    /**
     * @param Execution $execution
     * @param positive-int|-1 $times
     */
    public function __construct(
        callable $execution,
        private readonly Time $schedule,
        private readonly int $times,
        private readonly bool $reference,
        private readonly Runner $runner,
    ) {
        $this->execution = $execution;
    }

    public function run(
        \DateTimeImmutable $time,
        ?Cancellation $cancellation = null,
    ): void {
        $epoch = 0;
        foreach ($this->schedule->iterator($time) as $tick) {
            $suspension = EventLoop::getSuspension();
            $timerId = EventLoop::delay($tick->getTimestamp() - $time->getTimestamp(), $suspension->resume(...));
            $cancellationId = $cancellation?->subscribe($suspension->throw(...));

            if (!$this->reference) {
                EventLoop::unreference($timerId);
            }

            try {
                $suspension->suspend();
                $this->execute($tick, ++$epoch);
            } catch (CancelledException) {
                return;
            } finally {
                EventLoop::cancel($timerId);

                if ($cancellationId !== null) {
                    $cancellation?->unsubscribe($cancellationId);
                }
            }

            if ($this->times !== -1 && $this->times <= $epoch) {
                return;
            }

            $time = $tick;
        }
    }

    /**
     * @param positive-int $epoch
     * @throws CancelledException
     */
    private function execute(\DateTimeImmutable $time, int $epoch): void
    {
        $this->runner->run(function () use ($time, $epoch): void {
            ($this->execution)($time, $epoch);
        });
    }
}
