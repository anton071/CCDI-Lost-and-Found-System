<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('claims')) {
            return;
        }

        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('found_report_id')->index()->constrained('item_reports')->cascadeOnDelete();
            $table->foreignId('claimant_user_id')->index()->constrained('users')->cascadeOnDelete();
            $table->foreignId('related_lost_report_id')->nullable()->index()->constrained('item_reports')->nullOnDelete();
            $table->text('claim_message');
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected', 'cancelled'])
                ->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
