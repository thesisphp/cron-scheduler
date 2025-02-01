<?php

declare(strict_types=1);

namespace Thesis\Cron;

/**
 * @api
 */
interface Runner
{
    /**
     * @param \Closure(): void $task
     */
    public function run(\Closure $task): void;
}
