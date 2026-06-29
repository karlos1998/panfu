<?php

namespace App\Domain\Panfu\Services;

use App\Domain\Panfu\Repositories\FlashClientRepository;

class FlashClientService
{
    public function __construct(private readonly FlashClientRepository $clients) {}

    /**
     * @return array<string, mixed>
     */
    public function getPlayPage(): array
    {
        $client = $this->clients->getClient();
        $client['flashvarsQuery'] = http_build_query(
            $client['flashvars'],
            '',
            '&',
            PHP_QUERY_RFC3986,
        );

        return $client;
    }
}
