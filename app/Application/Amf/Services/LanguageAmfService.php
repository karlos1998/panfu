<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfResponseFactory;
use App\Application\Amf\ValueObjectFactory;
use App\Domain\Chat\SafeChatService;
use App\Infrastructure\Amf\TypedObject;

final class LanguageAmfService
{
    public function __construct(
        private readonly AmfResponseFactory $responses,
        private readonly ValueObjectFactory $valueObjects,
        private readonly SafeChatService $safeChat,
    ) {}

    public function getSecureChatSnippets(string $language, string $type): TypedObject
    {
        return $this->responses->make(
            message: $type,
            valueObject: $this->valueObjects->make('SecurityChatItem', ['children' => $this->safeChat->snippets()]),
        );
    }
}
