<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('image_features')) {
            return;
        }

        Schema::create('image_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_image_id')->constrained('item_images')->cascadeOnDelete();
            $table->enum('algorithm', ['SIFT', 'ORB']);
            $table->string('descriptor_path', 500)->nullable();
            $table->unsignedInteger('keypoint_count')->nullable();
            $table->unsignedInteger('image_width')->nullable();
            $table->unsignedInteger('image_height')->nullable();
            $table->enum('processing_status', ['pending', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('extracted_at')->nullable();
            $table->timestamps();

            $table->unique(['item_image_id', 'algorithm'], 'image_features_image_algorithm_unique');
            $table->index(['algorithm', 'processing_status'], 'image_features_algorithm_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_features');
    }
};
