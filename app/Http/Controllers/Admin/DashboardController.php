<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClaimStatus;
use App\Enums\MatchStatus;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Claim;
use App\Models\ImageMatchResult;
use App\Models\ItemRelease;
use App\Models\ItemReport;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $claimsOpen = Claim::whereIn('status', [ClaimStatus::Pending, ClaimStatus::UnderReview]);

        return view('admin.dashboard', [
            'stats' => [
                'lost' => ItemReport::ofType(ReportType::Lost)->whereIn('report_status', [ReportStatus::Published, ReportStatus::PendingReview])->count(),
                'found' => ItemReport::ofType(ReportType::Found)->whereIn('report_status', [ReportStatus::Published, ReportStatus::PendingReview])->count(),
                'pending_claims' => (clone $claimsOpen)->count(),
                'unresolved' => ItemReport::where('report_status', ReportStatus::Published)->count(),
                'returned' => ItemRelease::count(),
            ],
            'tasks' => [
                'reports' => ItemReport::where('report_status', ReportStatus::PendingReview)->count(),
                'claims' => (clone $claimsOpen)->count(),
                'matches' => ImageMatchResult::where('match_status', MatchStatus::PendingReview)->count(),
            ],
            'pendingReports' => ItemReport::where('report_status', ReportStatus::PendingReview)->with('category')->oldest()->limit(5)->get(),
            'pendingClaims' => (clone $claimsOpen)->with(['foundReport', 'claimant'])->oldest()->limit(5)->get(),
            'activity' => ActivityLog::with('user')->latest()->limit(8)->get(),
        ]);
    }
}
