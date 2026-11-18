<?php

namespace App\Services;

use App\Enums\ReportStatus;
use App\Models\ItemReport;
use App\Models\User;
use App\Notifications\ReportStatusChanged;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ReportModerationService
{
    public function __construct(private ImageMatchingService $matcher) {}

    /** @return array{0: ItemReport, 1: ?MatchOutcome} */
    public function transition(ItemReport $report, ReportStatus $to, User $admin, ?string $notes = null): array
    {
        if (! $admin->isAdmin()) {
            throw new AuthorizationException('Only administrators can change report status.');
        }
        if ($to === ReportStatus::Rejected && blank($notes)) {
            throw new ClaimException('A reason is required when rejecting a report.');
        }

        $report = DB::transaction(function () use ($report, $to, $admin, $notes) {
            $report = ItemReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
            if (! $report->report_status->canTransitionTo($to)) {
                throw new ClaimException("A {$report->report_status->label()} report cannot be changed to {$to->label()}.");
            }
            $from = $report->report_status;
            $report->report_status = $to;
            $report->reviewed_by = $admin->id;
            $report->reviewed_at = now();
            $report->review_notes = $notes ?: $report->review_notes;
            $report->rejection_reason = $to === ReportStatus::Rejected ? $notes : null;
            if ($to === ReportStatus::Resolved) {
                $report->resolved_at = now();
            }
            $report->save();

            ActivityLogger::log('report.'.$to->value, $report, "Report {$report->reference_code}: {$from->label()} → {$to->label()}.", ['notes' => $notes], $admin);

            return $report;
        });

        $report->submitter?->notify(new ReportStatusChanged(
            $report,
            "Your report {$report->reference_code} is now {$to->label()}",
            $to === ReportStatus::Rejected ? $notes : null,
        ));

        $outcome = ($to === ReportStatus::Published && $report->images()->exists())
            ? $this->matcher->matchReport($report)
            : null;

        return [$report, $outcome];
    }
}
