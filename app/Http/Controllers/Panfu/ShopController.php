<?php

namespace App\Http\Controllers\Panfu;

use App\Domain\Panfu\Services\ShopService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function __construct(private readonly ShopService $shops) {}

    public function __invoke(Request $request): JsonResponse
    {
        return response()->json($this->shops->getCatalogueFor($request->user()));
    }
}
