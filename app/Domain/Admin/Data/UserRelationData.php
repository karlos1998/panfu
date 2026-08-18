<?php

namespace App\Domain\Admin\Data;

use App\Enums\RelationType;

readonly class UserRelationData
{
    public function __construct(
        public int $relatedUserId,
        public RelationType $type,
    ) {}
}
