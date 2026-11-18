<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'item_report_id', 'uploaded_by', 'file_path', 'original_filename',
    'mime_type', 'file_size_bytes', 'sha256_hash', 'is_primary',
])]
class ItemImage extends Model
{
    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'file_size_bytes' => 'integer'];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(ItemReport::class, 'item_report_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function features(): HasMany
    {
        return $this->hasMany(ImageFeature::class);
    }
}
