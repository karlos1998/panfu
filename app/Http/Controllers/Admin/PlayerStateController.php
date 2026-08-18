<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\Services\AdminUserMutationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePlayerStateRequest;
use App\Models\PlayerState;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class PlayerStateController extends Controller
{
    public function __construct(private readonly AdminUserMutationService $users) {}

    public function store(StorePlayerStateRequest $request, User $user): RedirectResponse
    {
        $this->users->addState($user, $request->toData());

        return back()->with('success', 'Stan osiągnięcia został zapisany.');
    }

    public function update(StorePlayerStateRequest $request, User $user, PlayerState $state): RedirectResponse
    {
        $this->users->updateState($user, $state, $request->toData());

        return back()->with('success', 'Stan osiągnięcia został zaktualizowany.');
    }

    public function destroy(User $user, PlayerState $state): RedirectResponse
    {
        $this->users->removeState($user, $state);

        return back()->with('success', 'Stan osiągnięcia został usunięty.');
    }
}
