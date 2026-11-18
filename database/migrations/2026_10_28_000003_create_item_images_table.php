<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('item_images')) {
            return;
        }

        Schema::create('item_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_report_id')->index()->constrained('item_reports')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('file_path', 500);
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->char('sha256_hash', 64)->nullable()->index();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_images');
    }
};
