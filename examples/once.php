<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Thesis\Cron;

$scheduler = (new Cron\Scheduler())
    ->schedule('* * * * * *', Cron\task(var_dump(...))->once());

$scheduler->run();

\Amp\trapSignal([\SIGINT, \SIGTERM]);
