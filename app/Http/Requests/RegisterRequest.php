<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            // Admin is deliberately not an option: nobody can self-register as an administrator.
            'role' => ['required', Rule::in(array_map(fn ($r) => $r->value, UserRole::registrable()))],
            'student_number' => ['nullable', 'string', 'max:60', 'unique:users,student_number'],
            'department' => ['nullable', 'string', 'max:150'],
            'contact_number' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\-\s]{6,40}$/'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ];
    }
}
