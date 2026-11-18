<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;

/*
 * Creates a new administrator, or promotes an existing account. The password is always
 * typed at the prompt (never passed as an argument, so it does not land in shell history).
 */
#[Signature('ccdi:make-admin {email : E-mail address of the administrator} {--name= : Display name (new accounts only)}')]
#[Description('Create or promote an administrator account securely')]
class MakeAdmin extends Command
{
    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));

        if (Validator::make(['email' => $email], ['email' => 'required|email'])->fails()) {
            $this->error('That is not a valid e-mail address.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            if ($user->role === UserRole::Admin && $user->is_active) {
                $this->info('That account is already an active administrator.');

                return self::SUCCESS;
            }
            if (! confirm("Promote existing account {$user->email} to administrator?", false)) {
                return self::FAILURE;
            }
            $user->role = UserRole::Admin;
            $user->is_active = true;
            $user->save();
            ActivityLogger::log('admin.promoted_via_cli', $user, "Account {$user->email} promoted to administrator from the command line.");
            $this->info("{$user->email} is now an administrator.");

            return self::SUCCESS;
        }

        $name = $this->option('name') ?: 'Administrator';
        $secret = password('Choose a password (min. 12 characters)', required: true);
        $confirmation = password('Repeat the password', required: true);

        if ($secret !== $confirmation) {
            $this->error('The passwords do not match.');

            return self::FAILURE;
        }
        if (Validator::make(['password' => $secret], ['password' => 'min:12'])->fails()) {
            $this->error('The password must be at least 12 characters long.');

            return self::FAILURE;
        }

        $user = new User(['name' => $name, 'email' => $email, 'password' => $secret]);
        $user->role = UserRole::Admin;
        $user->is_active = true;
        $user->email_verified_at = now();
        $user->save();

        ActivityLogger::log('admin.created_via_cli', $user, "Administrator {$user->email} created from the command line.");
        $this->info("Administrator {$user->email} created.");

        return self::SUCCESS;
    }
}
