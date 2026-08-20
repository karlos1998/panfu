<?php

namespace App\Http\Controllers\Panfu;

use App\Domain\Panfu\Services\TeamPageService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function __construct(private readonly TeamPageService $teamPages) {}

    public function __invoke(): Response
    {
        return Inertia::render('Panfu/Team', $this->teamPages->getPage());
    }
}
