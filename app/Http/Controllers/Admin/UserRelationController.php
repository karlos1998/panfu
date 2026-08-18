<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\Services\AdminUserMutationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRelationRequest;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Http\RedirectResponse;

class UserRelationController extends Controller
{
    public function __construct(private readonly AdminUserMutationService $users) {}

    public function store(StoreUserRelationRequest $request, User $user): RedirectResponse
    {
        $this->users->addRelation($user, $request->toData());

        return back()->with('success', 'Relacja została zapisana.');
    }

    public function destroy(User $user, UserRelation $relation): RedirectResponse
    {
        $this->users->removeRelation($user, $relation);

        return back()->with('success', 'Relacja została usunięta.');
    }
}
