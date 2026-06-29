<?php

namespace App\Http\Controllers\Panfu;

use App\Domain\Panfu\Services\LandingPageService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(private readonly LandingPageService $landingPages) {}

    public function __invoke(): Response
    {
        return Inertia::render('Panfu/Home', $this->landingPages->getHomePage());
    }
}
