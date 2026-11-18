<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('item_reports')) {
            return;
        }

        Schema::create('item_reports', function (Blueprint $table) {
            $table->id();
            $table->string('reference_code', 30)->unique();
            $table->foreignId('submitted_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->index()->constrained('categories')->nullOnDelete();
            $table->enum('report_type', ['lost', 'found']);
            $table->string('item_name', 180)->index();
            $table->text('description');
            $table->string('brand', 120)->nullable();
            $table->string('color', 100)->nullable();
            $table->text('identifying_features')->nullable();
            $table->string('location_description', 255);
            $table->date('incident_date')->index();
            $table->time('incident_time')->nullable();
            $table->enum('report_status', ['pending_review', 'published', 'rejected', 'resolved', 'archived'])
                ->default('pending_review');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['report_type', 'report_status'], 'item_reports_type_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_reports');
    }
};
