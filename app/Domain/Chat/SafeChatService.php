<?php

namespace App\Domain\Chat;

use App\Application\Amf\ValueObjectFactory;
use App\Infrastructure\Amf\TypedObject;

final class SafeChatService
{
    public function __construct(private readonly ValueObjectFactory $valueObjects) {}

    /** @return list<TypedObject> */
    public function snippets(): array
    {
        $contents = file_get_contents(resource_path('data/game/safe-chat.json')) ?: '[]';
        $entries = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        return array_map(fn (array $entry): TypedObject => $this->item($entry), $entries);
    }

    /** @param array<string, mixed> $entry */
    private function item(array $entry): TypedObject
    {
        return $this->valueObjects->make('SecurityChatItem', [
            'label' => (string) ($entry['label'] ?? '').' ',
            'children' => array_map(
                fn (array $child): TypedObject => $this->item($child),
                $entry['children'] ?? [],
            ),
        ]);
    }
}
