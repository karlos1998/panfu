<?php

namespace App\Domain\Admin\Data;

readonly class PlayerStateData
{
    public function __construct(
        public int $category,
        public int $name,
        public int $value,
    ) {}
}
