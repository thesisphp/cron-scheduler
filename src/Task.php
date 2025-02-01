<?php

declare(strict_types=1);

namespace Thesis\Cron;

/**
 * @phpstan-type Execution = callable(\DateTimeImmutable, positive-int): void
 */
final class Task
{
    /** @var Execution */
    private $f;

    /**
     * @internal
     * @param Execution $f
     * @param -1|positive-int $times
     */
    public function __construct(
        callable $f,
        public readonly bool $reference = true,
        public readonly int $times = -1,
        public readonly ?Runner $runner = null,
    ) {
        $this->f = $f;
    }

    public function unreference(): self
    {
        return new self(
            f: $this->f,
            reference: false,
            times: $this->times,
            runner: $this->runner,
        );
    }

    public function once(): self
    {
        return $this->exactly(1);
    }

    /**
     * @param positive-int $times
     */
    public function exactly(int $times): self
    {
        return new self(
            f: $this->f,
            reference: $this->reference,
            times: $times,
            runner: $this->runner,
        );
    }

    public function onRunner(Runner $runner): self
    {
        return new self(
            f: $this->f,
            reference: $this->reference,
            times: $this->times,
            runner: $runner,
        );
    }

    /**
     * @internal
     * @param positive-int $epoch
     */
    public function execute(\DateTimeImmutable $time, int $epoch): void
    {
        $this->runner?->run(function () use ($time, $epoch): void {
            ($this->f)($time, $epoch);
        });
    }
}

/**
 * @api
 * @param callable(\DateTimeImmutable, positive-int): void $f
 */
function task(callable $f): Task
{
    return new Task($f);
}
