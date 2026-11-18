<?php

namespace App\Enums;

enum ReportStatus: string
{
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Rejected = 'rejected';
    case Resolved = 'resolved';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::PendingReview => 'Pending review',
            self::Published => 'Published',
            self::Rejected => 'Rejected',
            self::Resolved => 'Resolved',
            self::Archived => 'Archived',
        };
    }

    /** Permitted administrative transitions. Anything not listed is refused. */
    public function allowedNext(): array
    {
        return match ($this) {
            self::PendingReview => [self::Published, self::Rejected],
            self::Published => [self::Resolved, self::Archived],
            self::Rejected => [self::PendingReview, self::Archived],
            self::Resolved => [self::Archived],
            self::Archived => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }
}
