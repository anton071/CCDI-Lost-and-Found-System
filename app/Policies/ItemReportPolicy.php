<?php

namespace App\Policies;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\ItemReport;
use App\Models\User;

class ItemReportPolicy
{
    /** Published reports are listed publicly (with limited fields); everything else is owner/admin only. */
    public function view(?User $user, ItemReport $report): bool
    {
        return $report->report_status === ReportStatus::Published || $this->viewPrivate($user, $report);
    }

    /** Full record incl. verification details, review notes and potential matches. */
    public function viewPrivate(?User $user, ItemReport $report): bool
    {
        return $user !== null && $user->is_active
            && ($user->isAdmin() || $report->submitted_by === $user->id);
    }

    /** Photographs of FOUND items are shown to signed-in members only, to make false claims harder. */
    public function viewImage(?User $user, ItemReport $report): bool
    {
        if ($this->viewPrivate($user, $report)) {
            return true;
        }
        if ($report->report_status !== ReportStatus::Published) {
            return false;
        }

        return $report->report_type === ReportType::Lost || ($user !== null && $user->is_active);
    }

    public function create(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function claim(User $user, ItemReport $report): bool
    {
        return $user->is_active
            && $report->report_type === ReportType::Found
            && $report->report_status === ReportStatus::Published
            && $report->submitted_by !== $user->id;
    }

    public function viewMatches(User $user, ItemReport $report): bool
    {
        return $user->isAdmin() || ($report->isLost() && $report->submitted_by === $user->id);
    }
}
