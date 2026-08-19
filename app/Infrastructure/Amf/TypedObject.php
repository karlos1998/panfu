<?php

namespace App\Infrastructure\Amf;

use JsonSerializable;

final class TypedObject implements JsonSerializable
{
    /** @param array<string, mixed> $properties */
    public function __construct(
        public readonly string $type,
        private array $properties = [],
    ) {}

    public function get(string $name, mixed $default = null): mixed
    {
        return $this->properties[$name] ?? $default;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->properties);
    }

    public function set(string $name, mixed $value): self
    {
        $this->properties[$name] = $value;

        return $this;
    }

    /** @return array<string, mixed> */
    public function properties(): array
    {
        return $this->properties;
    }

    /** @return array{type: string, properties: array<string, mixed>} */
    public function jsonSerialize(): array
    {
        return ['type' => $this->type, 'properties' => $this->properties];
    }
}
