<?php

namespace App\Domain\Pan\Facts;

use App\Data\PanResult;

final readonly class PanFacts
{
    public function __construct(private PanResult $pan) {}

    public static function from(PanResult $pan): self
    {
        return new self($pan);
    }

    public function get(string $key): mixed
    {
        return $this->pan->get($key);
    }

    /** @return array<string, mixed> */
    public function calculationTrace(): array
    {
        $trace = $this->pan->get('calculationTrace');

        return is_array($trace) ? $trace : [];
    }

    public function hasPlatePattern(string $pattern): bool
    {
        return in_array($pattern, $this->calculationTrace()['plate_patterns'] ?? [], true);
    }

    public function hasLessonPattern(string $pattern): bool
    {
        return in_array($pattern, $this->calculationTrace()['lesson_patterns'] ?? [], true);
    }

    public function chuchuanMethod(): ?string
    {
        $trace = $this->calculationTrace()['initial_transmission'] ?? [];

        if (($trace['recorded'] ?? false) !== true) {
            return null;
        }

        $method = $trace['method'] ?? null;

        return is_string($method) ? $method : null;
    }

    public function sanchuanMethod(string $stage): ?string
    {
        $trace = $this->calculationTrace()[$stage.'_transmission'] ?? [];

        if (($trace['recorded'] ?? false) !== true) {
            return null;
        }

        $method = $trace['method'] ?? null;

        return is_string($method) ? $method : null;
    }

    /** @return array<string, mixed> */
    public function chuchuanEvidence(): array
    {
        $evidence = $this->calculationTrace()['initial_transmission']['evidence'] ?? [];

        return is_array($evidence) ? $evidence : [];
    }
}
