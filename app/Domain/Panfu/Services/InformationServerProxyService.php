<?php

namespace App\Domain\Panfu\Services;

use App\Domain\Panfu\Gateways\InformationServerGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InformationServerProxyService
{
    public function __construct(private readonly InformationServerGateway $gateway) {}

    public function forward(Request $request, string $path = ''): Response
    {
        try {
            $upstream = $this->gateway->forward($request, $path);
        } catch (ConnectionException) {
            return response('Information server unavailable.', Response::HTTP_BAD_GATEWAY);
        }

        return response($upstream->body(), $upstream->status())
            ->header('Content-Type', $upstream->header('Content-Type', 'application/x-amf'));
    }
}
