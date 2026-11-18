<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register', ['roles' => UserRole::registrable()]);
    }

    public function store(RegisterRequest $request)
    {
        $data = $request->validated();

        $user = new User(collect($data)->only(['name', 'email', 'password', 'student_number', 'department', 'contact_number'])->all());
        // Role comes from a validated whitelist that excludes admin; it is never mass-assigned.
        $user->role = UserRole::from($data['role']);
        $user->is_active = true;
        $user->save();

        ActivityLogger::log('user.registered', $user, "New {$user->role->value} account registered.", [], $user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Welcome to CCDI Lost and Found. Your account is ready.');
    }
}
