<?php

namespace App\Infrastructure\Amf;

final class UndefinedValue
{
    private function __construct() {}

    public static function instance(): self
    {
        static $instance;

        return $instance ??= new self;
    }
}
