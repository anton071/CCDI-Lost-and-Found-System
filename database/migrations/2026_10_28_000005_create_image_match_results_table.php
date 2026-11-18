<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('image_match_results')) {
            return;
        }

        Schema::create('image_match_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lost_report_id');
            $table->unsignedBigInteger('found_report_id');
            $table->enum('algorithm_used', ['SIFT', 'ORB']);
            $table->decimal('similarity_score', 7, 4)->default(0);
            $table->unsignedInteger('good_matches')->nullable();
            $table->unsignedInteger('lost_keypoints')->nullable();
            $table->unsignedInteger('found_keypoints')->nullable();
            $table->json('matching_details')->nullable();
            $table->enum('match_status', ['pending_review', 'reviewed', 'dismissed', 'confirmed_match'])
                ->default('pending_review');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['lost_report_id', 'found_report_id'], 'image_match_pair_index');
            $table->index('similarity_score', 'image_match_score_index');
            $table->index('match_status', 'image_match_status_index');

            $table->foreign('lost_report_id', 'image_match_lost_report_id_foreign')
                ->references('id')->on('item_reports')->cascadeOnDelete();
            $table->foreign('found_report_id', 'image_match_found_report_id_foreign')
                ->references('id')->on('item_reports')->cascadeOnDelete();
            $table->foreign('reviewed_by', 'image_match_reviewed_by_foreign')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_match_results');
    }
};
