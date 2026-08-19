<?php

namespace App\Http\Controllers;

use App\Application\Amf\AmfGateway;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AmfGatewayController extends Controller
{
    public function __construct(private readonly AmfGateway $gateway) {}

    public function __invoke(Request $request, ?string $path = null): Response
    {
        return response($this->gateway->handle($request->getContent()))
            ->header('Content-Type', 'application/x-amf');
    }
}
