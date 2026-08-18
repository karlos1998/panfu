<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\Services\AdminUserMutationService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class UserSessionController extends Controller
{
    public function __construct(private readonly AdminUserMutationService $users) {}

    public function destroy(User $user, string $session): RedirectResponse
    {
        $this->users->revokeSession($user, $session);

        return back()->with('success', 'Sesja została unieważniona.');
    }
}
