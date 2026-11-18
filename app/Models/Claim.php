<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

// status / reviewed_* are set only by ClaimService, never from request input.
#[Fillable(['related_lost_report_id', 'claim_message'])]
class Claim extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['status' => ClaimStatus::class, 'reviewed_at' => 'datetime'];
    }

    public function foundReport(): BelongsTo
    {
        return $this->belongsTo(ItemReport::class, 'found_report_id');
    }

    public function relatedLostReport(): BelongsTo
    {
        return $this->belongsTo(ItemReport::class, 'related_lost_report_id');
    }

    public function claimant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimant_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function release(): HasOne
    {
        return $this->hasOne(ItemRelease::class);
    }
}
