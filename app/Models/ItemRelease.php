<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'claim_id', 'found_report_id', 'released_by', 'released_to_user_id',
    'release_reference', 'verification_notes', 'released_at',
])]
class ItemRelease extends Model
{
    protected function casts(): array
    {
        return ['released_at' => 'datetime'];
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    public function foundReport(): BelongsTo
    {
        return $this->belongsTo(ItemReport::class, 'found_report_id');
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function releasedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_to_user_id');
    }
}
