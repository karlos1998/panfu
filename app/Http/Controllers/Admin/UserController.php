<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\Services\AdminUserMutationService;
use App\Domain\Admin\Services\AdminUserService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexUsersRequest;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly AdminUserService $users,
        private readonly AdminUserMutationService $mutations,
    ) {}

    public function index(IndexUsersRequest $request): Response
    {
        return Inertia::render('Admin/Users/Index', $this->users->paginatedUsers($request->filters()));
    }

    public function show(Request $request, User $user): Response
    {
        return Inertia::render('Admin/Users/Show', $this->users->userDetails($user, $request->session()->getId()));
    }

    public function update(UpdateAdminUserRequest $request, User $user): RedirectResponse
    {
        $this->mutations->updateUser($request->user(), $user, $request->toData());

        return back()->with('success', 'Dane pandy zostały zapisane.');
    }
}
