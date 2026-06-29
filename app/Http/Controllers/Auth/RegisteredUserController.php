<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Services\RegistrationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function __construct(private readonly RegistrationService $registration) {}

    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(RegisterUserRequest $request): RedirectResponse
    {
        $this->registration->register($request->toData());

        return redirect(route('play', absolute: false));
    }
}
