<?php

namespace App\Services;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\ItemReport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReportService
{
    public function __construct(private ImageStorage $images, private ImageMatchingService $matcher) {}

    /**
     * @param  array<string,mixed>  $data  already validated fields
     * @return array{0: ItemReport, 1: ?MatchOutcome}
     */
    public function create(User $user, ReportType $type, array $data, ?UploadedFile $photo = null): array
    {
        $report = DB::transaction(function () use ($user, $type, $data, $photo) {
            $report = new ItemReport($data);
            $report->report_type = $type;
            $report->reference_code = $this->makeReference($type);
            $report->submitted_by = $user->id;
            $report->report_status = ($type === ReportType::Lost && config('ccdi.auto_publish_lost'))
                ? ReportStatus::Published
                : ReportStatus::PendingReview;
            $report->save();

            if ($photo) {
                $this->images->store($photo, $report, $user);
            }

            return $report;
        });

        ActivityLogger::log('report.created', $report, ucfirst($type->value)." report {$report->reference_code} submitted.", [], $user);

        // Matching only starts for listed reports. Found reports are matched when an administrator publishes them.
        $outcome = ($photo && $report->report_status === ReportStatus::Published)
            ? $this->matcher->matchReport($report)
            : null;

        return [$report, $outcome];
    }

    private function makeReference(ReportType $type): string
    {
        do {
            $code = strtoupper(substr($type->value, 0, 1)).'-'.now()->format('ymd').'-'.strtoupper(Str::random(5));
        } while (ItemReport::where('reference_code', $code)->exists());

        return $code;
    }
}
