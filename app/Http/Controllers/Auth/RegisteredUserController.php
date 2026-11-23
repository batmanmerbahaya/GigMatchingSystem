<?php

namespace App\Http\Controllers\Auth;

use App\Enums\EntityType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Throwable;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register', [
            'roles' => UserRole::cases(),
            'entityTypes' => EntityType::cases(),
        ]);
    }

    /**
     * Register with one role. The account works straight away; email
     * verification is only needed later, to post or apply for a job.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
            ]);

            if ($user->isGigWorker()) {
                $user->gigWorker()->create([]);
            } else {
                $isBusiness = $validated['entity_type'] === EntityType::Business->value;

                $user->contractEntity()->create([
                    'entity_type' => $validated['entity_type'],
                    'organisation_name' => $isBusiness ? $validated['organisation_name'] : null,
                ]);
            }

            return $user;
        });

        // Sends the verification email. A mail failure must not block
        // registration, because the user can resend it later.
        try {
            event(new Registered($user));
        } catch (Throwable $exception) {
            report($exception);
        }

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
