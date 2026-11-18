<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('item_releases')) {
            return;
        }

        Schema::create('item_releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->unique()->constrained('claims')->restrictOnDelete();
            $table->foreignId('found_report_id')->index()->constrained('item_reports')->restrictOnDelete();
            $table->foreignId('released_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->foreignId('released_to_user_id')->constrained('users')->restrictOnDelete();
            $table->string('release_reference', 40)->nullable()->unique();
            $table->text('verification_notes')->nullable();
            // Explicit nullable-free default avoids MariaDB's implicit ON UPDATE CURRENT_TIMESTAMP behaviour.
            $table->timestamp('released_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_releases');
    }
};
