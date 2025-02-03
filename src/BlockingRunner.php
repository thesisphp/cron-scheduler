<?php

declare(strict_types=1);

namespace Thesis\Cron;

/**
 * @api
 *
 * Will block the scheduling loop (not the event loop) until the task is completed.
 * Necessary when you want to ensure that the next task will not be executed until the current task is completed.
 */
final class BlockingRunner implements Runner
{
    public function run(\Closure $task): void
    {
        $task();
    }
}
