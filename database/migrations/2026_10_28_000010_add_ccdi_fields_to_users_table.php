<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * The imported SQL file already creates these columns inside the `users` table
 * (and records 0001_01_01_000000_create_users_table as run). A fresh
 * `php artisan migrate` however builds the stock Laravel users table, so this
 * migration adds the CCDI columns only where they are missing. It is a no-op on a
 * database imported from ccdi_lost_found_database.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['student', 'teacher', 'staff', 'admin'])->default('student')->after('password');
            }
            if (! Schema::hasColumn('users', 'student_number')) {
                $table->string('student_number', 60)->nullable()->after('role');
            }
            if (! Schema::hasColumn('users', 'department')) {
                $table->string('department', 150)->nullable()->after('student_number');
            }
            if (! Schema::hasColumn('users', 'contact_number')) {
                $table->string('contact_number', 40)->nullable()->after('department');
            }
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('contact_number');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasIndex('users', 'users_student_number_unique')) {
                $table->unique('student_number');
            }
            if (! Schema::hasIndex('users', 'users_role_index')) {
                $table->index('role');
            }
            if (! Schema::hasIndex('users', 'users_is_active_index')) {
                $table->index('is_active');
            }
        });
    }

    public function down(): void
    {
        // Intentionally conservative: on an imported database these columns belong to the base schema.
        Schema::table('users', function (Blueprint $table) {
            foreach (['users_student_number_unique', 'users_role_index', 'users_is_active_index'] as $index) {
                if (Schema::hasIndex('users', $index)) {
                    $table->dropIndex($index);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $columns = array_filter(
                ['role', 'student_number', 'department', 'contact_number', 'is_active'],
                fn ($c) => Schema::hasColumn('users', $c)
            );
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
