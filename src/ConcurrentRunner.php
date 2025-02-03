<?php

declare(strict_types=1);

namespace Thesis\Cron;

use Revolt\EventLoop;

/**
 * @api
 *
 * Gives the execution of the task to the event loop (@see EventLoop::queue) and immediately schedules the next task run.
 * This runner is used by default.
 */
final class ConcurrentRunner implements Runner
{
    public function run(\Closure $task): void
    {
        EventLoop::queue($task);
    }
}
