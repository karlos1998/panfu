<?php

namespace App\Domain\Panfu\Gateways;

use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\Request;

interface InformationServerGateway
{
    public function forward(Request $request, string $path): ClientResponse;
}
