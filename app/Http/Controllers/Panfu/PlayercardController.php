<?php

namespace App\Http\Controllers\Panfu;

use App\Domain\Panfu\Services\PandaPlayercardService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panfu\PlayercardRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PlayercardController extends Controller
{
    public function __invoke(PlayercardRequest $request, PandaPlayercardService $playercards): BinaryFileResponse
    {
        return response()->file($playercards->forUsername($request->username()), [
            'Cache-Control' => 'public, max-age=3600',
            'Content-Type' => 'image/png',
        ]);
    }
}
