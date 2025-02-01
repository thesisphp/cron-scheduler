<?php

declare(strict_types=1);

namespace Thesis\Cron;

/**
 * @phpstan-type Execution = callable(\DateTimeImmutable): void
 */
final class Task
{
    /** @var Execution */
    private $f;

    /**
     * @internal
     * @param Execution $f
     */
    public function __construct(
        callable $f,
        public readonly bool $reference = true,
        public readonly bool $once = false,
        public readonly ?Runner $runner = null,
    ) {
        $this->f = $f;
    }

    public function unreference(): self
    {
        return new self(
            f: $this->f,
            reference: false,
        );
    }

    public function once(): self
    {
        return new self(
            f: $this->f,
            reference: $this->reference,
            once: true,
        );
    }

    public function onRunner(Runner $runner): self
    {
        return new self(
            f: $this->f,
            reference: $this->reference,
            once: $this->once,
            runner: $runner,
        );
    }

    /**
     * @internal
     */
    public function execute(\DateTimeImmutable $time): void
    {
        $this->runner?->run(function () use ($time): void {
            ($this->f)($time);
        });
    }
}

/**
 * @api
 * @param callable(\DateTimeImmutable): void $f
 */
function task(callable $f): Task
{
    return new Task($f);
}
