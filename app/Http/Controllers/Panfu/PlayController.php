<?php

namespace App\Http\Controllers\Panfu;

use App\Domain\Panfu\Services\FlashClientService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlayController extends Controller
{
    public function __construct(private readonly FlashClientService $flashClients) {}

    public function __invoke(Request $request): Response
    {
        return Inertia::render('Panfu/Play', [
            'client' => $this->flashClients->getPlayPage($request->user()),
        ]);
    }
}
