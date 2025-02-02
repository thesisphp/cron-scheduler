<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Thesis\Cron;
use function Amp\trapSignal;

$scheduler = (new Cron\Scheduler())
    ->schedule('* * * * * *', Cron\run(var_dump(...))->once());

$scheduler->run();

trapSignal([\SIGINT, \SIGTERM]);
