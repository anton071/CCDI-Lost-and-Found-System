<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case Teacher = 'teacher';
    case Staff = 'staff';
    case Admin = 'admin';

    /** Roles a person may pick for themselves at registration. Admin is never self-assignable. */
    public static function registrable(): array
    {
        return [self::Student, self::Teacher, self::Staff];
    }

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::Teacher => 'Teacher',
            self::Staff => 'Staff',
            self::Admin => 'Administrator',
        };
    }
}
