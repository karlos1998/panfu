<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\Services\AdminDashboardService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly AdminDashboardService $dashboard) {}

    public function __invoke(): Response
    {
        return Inertia::render('Admin/Dashboard', $this->dashboard->dashboard());
    }
}
