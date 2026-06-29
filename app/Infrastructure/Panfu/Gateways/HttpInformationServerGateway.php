<?php

namespace App\Infrastructure\Panfu\Gateways;

use App\Domain\Panfu\Gateways\InformationServerGateway;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class HttpInformationServerGateway implements InformationServerGateway
{
    public function forward(Request $request, string $path): ClientResponse
    {
        $url = $this->url($path);
        $headers = $this->headers($request);
        $pendingRequest = Http::withHeaders($headers)
            ->connectTimeout(3)
            ->timeout(15);

        if ($request->getContent() !== '') {
            $pendingRequest = $pendingRequest->withBody(
                $request->getContent(),
                $request->header('Content-Type', 'application/x-amf'),
            );
        }

        return $pendingRequest->send($request->method(), $url, [
            'query' => $request->query(),
        ]);
    }

    private function url(string $path): string
    {
        $baseUrl = rtrim((string) config('panfu.game_client.legacy_information_server'), '/');

        return $baseUrl.'/'.ltrim($path, '/');
    }

    /**
     * @return array<string, string>
     */
    private function headers(Request $request): array
    {
        return array_filter([
            'Accept' => $request->header('Accept'),
            'Content-Type' => $request->header('Content-Type'),
            'User-Agent' => $request->header('User-Agent'),
        ]);
    }
}
