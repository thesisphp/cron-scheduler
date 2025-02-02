<?php

declare(strict_types=1);

namespace Thesis\Cron;

/**
 * @api
 * @phpstan-type Execution = callable(\DateTimeImmutable, positive-int): void
 */
final class Run
{
    /** @var Execution */
    private $f;

    /**
     * @param Execution $f
     * @param positive-int|-1 $times
     */
    public function __construct(
        callable $f,
        public readonly int $times = -1,
        public readonly bool $reference = true,
        public readonly ?Runner $runner = null,
    ) {
        $this->f = $f;
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
            times: $times,
            reference: $this->reference,
            runner: $this->runner,
        );
    }

    public function unreference(): self
    {
        return new self(
            f: $this->f,
            times: $this->times,
            reference: false,
            runner: $this->runner,
        );
    }

    public function onRunner(Runner $runner): self
    {
        return new self(
            f: $this->f,
            times: $this->times,
            reference: $this->reference,
            runner: $runner,
        );
    }

    /**
     * @return Execution
     */
    public function execution(): callable
    {
        return $this->f;
    }
}

/**
 * @api
 * @param callable(\DateTimeImmutable, positive-int): void $f
 */
function run(callable $f): Run
{
    return new Run($f);
}
