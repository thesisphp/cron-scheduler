<?php

declare(strict_types=1);

namespace Thesis\Cron;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Revolt\EventLoop;

#[CoversClass(Scheduler::class)]
final class SchedulerTest extends TestCase
{
    public function testExactly(): void
    {
        /** @var array<positive-int, \DateTimeImmutable> $invokes */
        $invokes = [];

        $callback = static function (\DateTimeImmutable $time, int $epoch) use (&$invokes): void {
            $invokes[$epoch] = $time;
        };

        $scheduler = new Scheduler();
        $scheduler = $scheduler->schedule(
            '* * * * * *',
            run($callback)
                ->exactly(2),
        );

        $start = microtime(true);
        $scheduler->run(new \DateTimeImmutable('2025-01-29 10:00:00'));

        EventLoop::run();

        $end = microtime(true) - $start;

        self::assertGreaterThan(2, $end);
        self::assertLessThan(3, $end);
        self::assertEquals([
            1 => new \DateTimeImmutable('2025-01-29 10:00:01'),
            2 => new \DateTimeImmutable('2025-01-29 10:00:02'),
        ], $invokes);
    }

    public function testOnce(): void
    {
        /** @var array<positive-int, \DateTimeImmutable> $invokes */
        $invokes = [];

        $callback = static function (\DateTimeImmutable $time, int $epoch) use (&$invokes): void {
            $invokes[$epoch] = $time;
        };

        $scheduler = new Scheduler();
        $scheduler = $scheduler->schedule(
            '* * * * * *',
            run($callback)
                ->once(),
        );

        $start = microtime(true);
        $scheduler->run(new \DateTimeImmutable('2025-01-29 10:00:00'));

        EventLoop::run();

        $end = microtime(true) - $start;

        self::assertGreaterThan(1, $end);
        self::assertLessThan(2, $end);
        self::assertEquals([
            1 => new \DateTimeImmutable('2025-01-29 10:00:01'),
        ], $invokes);
    }

    public function testUnreference(): void
    {
        /** @var array<positive-int, \DateTimeImmutable> $invokes */
        $invokes = [];

        $callback = static function (\DateTimeImmutable $time, int $epoch) use (&$invokes): void {
            $invokes[$epoch] = $time;
        };

        $scheduler = new Scheduler();
        $scheduler = $scheduler->schedule(
            '* * * * * *',
            run($callback)
                ->once()
                ->unreference(),
        );

        $start = microtime(true);
        $scheduler->run(new \DateTimeImmutable('2025-01-29 10:00:00'));

        EventLoop::run();

        $end = microtime(true) - $start;

        self::assertLessThan(1, $end);
        self::assertCount(0, $invokes);
    }
}
