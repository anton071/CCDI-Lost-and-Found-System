<?php

namespace App\Models;

use App\Enums\MatchAlgorithm;
use App\Enums\MatchStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * A row here is a POTENTIAL match produced by SIFT/ORB. It is never proof of ownership.
 * match_status is changed only by authorised personnel through the review service.
 */
#[Fillable([
    'lost_report_id', 'found_report_id', 'algorithm_used', 'similarity_score', 'good_matches',
    'lost_keypoints', 'found_keypoints', 'matching_details',
])]
class ImageMatchResult extends Model
{
    protected function casts(): array
    {
        return [
            'algorithm_used' => MatchAlgorithm::class,
            'match_status' => MatchStatus::class,
            'similarity_score' => 'float',
            'matching_details' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function lostReport(): BelongsTo
    {
        return $this->belongsTo(ItemReport::class, 'lost_report_id');
    }

    public function foundReport(): BelongsTo
    {
        return $this->belongsTo(ItemReport::class, 'found_report_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
