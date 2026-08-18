<?php

namespace App\Domain\Admin\Data;

readonly class InventoryData
{
    public function __construct(
        public bool $active,
        public bool $bought,
        public int $x,
        public int $y,
        public int $rotation,
        public int $room,
    ) {}
}
