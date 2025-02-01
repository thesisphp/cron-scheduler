<?php

declare(strict_types=1);

namespace Thesis\Cron;

use Revolt\EventLoop;

/**
 * @api
 */
final class ConcurrentRunner implements Runner
{
    public function run(\Closure $task): void
    {
        EventLoop::queue($task);
    }
}
