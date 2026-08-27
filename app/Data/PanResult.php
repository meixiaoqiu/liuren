<?php

namespace App\Data;

final readonly class PanResult
{
    public function __construct(private array $data) {}

    public function toArray(): array
    {
        return $this->data;
    }

    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }
}
