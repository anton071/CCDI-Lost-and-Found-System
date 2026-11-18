<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\Like;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'role' => ['nullable', Rule::enum(UserRole::class)]]);

        return view('admin.users.index', [
            'users' => User::query()
                ->when($f['q'] ?? null, fn ($q, $v) => $q->where(function ($w) use ($v) {
                    Like::where($w, 'name', $v);
                    Like::where($w, 'email', $v, 'or');
                }))
                ->when($f['role'] ?? null, fn ($q, $v) => $q->where('role', $v))
                ->latest('id')->paginate(15)->withQueryString(),
            'filters' => $f,
            'roles' => UserRole::cases(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => ['required', 'boolean'],
        ]);

        $actor = $request->user();
        $newRole = UserRole::from($data['role']);
        $active = (bool) $data['is_active'];

        if ($user->is($actor) && ($newRole !== UserRole::Admin || ! $active)) {
            return back()->withErrors(['user' => 'You cannot remove your own administrator access.']);
        }
        $losesAdmin = $user->role === UserRole::Admin && ($newRole !== UserRole::Admin || ! $active);
        if ($losesAdmin && User::where('role', UserRole::Admin)->where('is_active', true)->whereKeyNot($user->id)->doesntExist()) {
            return back()->withErrors(['user' => 'At least one active administrator must remain.']);
        }

        $before = ['role' => $user->role->value, 'is_active' => $user->is_active];
        $user->role = $newRole;
        $user->is_active = $active;
        $user->save();

        ActivityLogger::log('user.access_changed', $user, "Access updated for {$user->email}.", ['before' => $before, 'after' => ['role' => $newRole->value, 'is_active' => $active]], $actor);

        return back()->with('status', "Access updated for {$user->name}.");
    }
}
