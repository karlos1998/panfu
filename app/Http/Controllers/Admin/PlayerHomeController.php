<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\Services\AdminPlayerHomeService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexPlayerHomesRequest;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class PlayerHomeController extends Controller
{
    public function __construct(private readonly AdminPlayerHomeService $homes) {}

    public function index(IndexPlayerHomesRequest $request): Response
    {
        return Inertia::render('Admin/Rooms/Homes/Index', $this->homes->paginatedHomes($request->filters()));
    }

    public function show(User $user): Response
    {
        return Inertia::render('Admin/Rooms/Homes/Show', $this->homes->homeDetails($user));
    }
}
