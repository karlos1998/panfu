<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\Services\AdminMinigameService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class MinigameController extends Controller
{
    public function __construct(private readonly AdminMinigameService $minigames) {}

    public function __invoke(): Response
    {
        return Inertia::render('Admin/Minigames/Index', $this->minigames->catalog());
    }
}
