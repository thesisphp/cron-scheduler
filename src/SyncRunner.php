<?php

declare(strict_types=1);

namespace Thesis\Cron;

/**
 * @api
 */
final class SyncRunner implements Runner
{
    public function run(\Closure $task): void
    {
        $task();
    }
}
