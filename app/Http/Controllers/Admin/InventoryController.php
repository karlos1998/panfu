<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Admin\Services\AdminUserMutationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInventoryRequest;
use App\Http\Requests\Admin\UpdateInventoryRequest;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class InventoryController extends Controller
{
    public function __construct(private readonly AdminUserMutationService $users) {}

    public function store(StoreInventoryRequest $request, User $user): RedirectResponse
    {
        $this->users->addInventory($user, $request->itemId(), $request->toData());

        return back()->with('success', 'Przedmiot został dodany.');
    }

    public function update(UpdateInventoryRequest $request, User $user, Inventory $inventory): RedirectResponse
    {
        $this->users->updateInventory($user, $inventory, $request->toData());

        return back()->with('success', 'Przedmiot został zaktualizowany.');
    }

    public function destroy(User $user, Inventory $inventory): RedirectResponse
    {
        $this->users->removeInventory($user, $inventory);

        return back()->with('success', 'Przedmiot został usunięty.');
    }
}
