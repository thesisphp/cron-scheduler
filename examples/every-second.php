<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Thesis\Cron;
use function Amp\trapSignal;

$scheduler = (new Cron\Scheduler())
    ->schedule('* * * * * *', var_dump(...));

$scheduler->run();

trapSignal([\SIGINT, \SIGTERM]);
