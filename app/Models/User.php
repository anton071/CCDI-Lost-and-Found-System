<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/*
 * `role` and `is_active` are deliberately NOT mass-assignable so that no request payload
 * can promote an account. They are only set explicitly in trusted code (seeders, the
 * ccdi:make-admin command, and admin user management).
 */
#[Fillable(['name', 'email', 'password', 'student_number', 'department', 'contact_number'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin && $this->is_active;
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ItemReport::class, 'submitted_by');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class, 'claimant_user_id');
    }
}
