<?php

namespace App\Extensions;

interface LiurenPlugin
{
    public function id(): string;

    /**
     * @return array<int, class-string>
     */
    public function providers(): array;
}
