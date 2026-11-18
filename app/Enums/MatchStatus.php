<?php

namespace App\Enums;

enum MatchStatus: string
{
    case PendingReview = 'pending_review';
    case Reviewed = 'reviewed';
    case Dismissed = 'dismissed';
    case ConfirmedMatch = 'confirmed_match';

    public function label(): string
    {
        return match ($this) {
            self::PendingReview => 'Awaiting review',
            self::Reviewed => 'Reviewed',
            self::Dismissed => 'Dismissed',
            self::ConfirmedMatch => 'Marked as likely pair',
        };
    }
}
