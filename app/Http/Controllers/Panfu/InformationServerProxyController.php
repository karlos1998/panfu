<?php

namespace App\Http\Controllers\Panfu;

use App\Domain\Panfu\Services\InformationServerProxyService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InformationServerProxyController extends Controller
{
    public function __construct(private readonly InformationServerProxyService $informationServer) {}

    public function __invoke(Request $request, ?string $path = null): Response
    {
        return $this->informationServer->forward($request, $path ?? '');
    }
}
