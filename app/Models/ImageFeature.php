<?php

namespace App\Models;

use App\Enums\MatchAlgorithm;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'item_image_id', 'algorithm', 'descriptor_path', 'keypoint_count', 'image_width',
    'image_height', 'processing_status', 'error_message', 'extracted_at',
])]
class ImageFeature extends Model
{
    protected function casts(): array
    {
        return ['algorithm' => MatchAlgorithm::class, 'extracted_at' => 'datetime'];
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(ItemImage::class, 'item_image_id');
    }
}
