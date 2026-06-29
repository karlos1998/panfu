<?php

namespace App\Http\Controllers\Panfu;

use App\Domain\Panfu\Services\FlashClientService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PlayController extends Controller
{
    public function __construct(private readonly FlashClientService $flashClients) {}

    public function __invoke(): Response
    {
        return Inertia::render('Panfu/Play', [
            'client' => $this->flashClients->getPlayPage(),
        ]);
    }
}
