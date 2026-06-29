<?php

namespace App\Infrastructure\Panfu\Gateways;

use App\Domain\Panfu\Gateways\InformationServerGateway;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class HttpInformationServerGateway implements InformationServerGateway
{
    private const COOKIE_SESSION_KEY = 'panfu.information_server.cookies';

    public function forward(Request $request, string $path): ClientResponse
    {
        $url = $this->url($path);
        $headers = $this->headers($request);
        $host = parse_url($url, PHP_URL_HOST);
        $pendingRequest = Http::withHeaders($headers)
            ->connectTimeout(3)
            ->timeout(15);

        if (is_string($host) && $host !== '') {
            $pendingRequest = $pendingRequest->withCookies($this->cookies($request), $host);
        }

        if ($request->getContent() !== '') {
            $pendingRequest = $pendingRequest->withBody(
                $request->getContent(),
                $request->header('Content-Type', 'application/x-amf'),
            );
        }

        $response = $pendingRequest->send($request->method(), $url, [
            'query' => $request->query(),
        ]);

        $this->storeCookies($request, $response);

        return $response;
    }

    private function url(string $path): string
    {
        $baseUrl = rtrim((string) config('panfu.game_client.information_server_upstream'), '/');

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

    /**
     * @return array<string, string>
     */
    private function cookies(Request $request): array
    {
        $cookies = $request->session()->get(self::COOKIE_SESSION_KEY, []);

        return is_array($cookies) ? $cookies : [];
    }

    private function storeCookies(Request $request, ClientResponse $response): void
    {
        $cookies = $this->cookies($request);

        foreach ($response->cookies()->toArray() as $cookie) {
            $name = $cookie['Name'] ?? null;

            if (! is_string($name) || $name === '') {
                continue;
            }

            $expires = $cookie['Expires'] ?? null;
            if (is_int($expires) && $expires !== 0 && $expires <= time()) {
                unset($cookies[$name]);

                continue;
            }

            $value = $cookie['Value'] ?? null;
            if (is_string($value)) {
                $cookies[$name] = $value;
            }
        }

        $request->session()->put(self::COOKIE_SESSION_KEY, $cookies);
    }
}
