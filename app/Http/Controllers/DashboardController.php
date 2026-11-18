<?php

namespace App\Http\Controllers;

use App\Enums\ClaimStatus;
use App\Enums\MatchStatus;
use App\Enums\ReportStatus;
use App\Models\ImageMatchResult;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $reports = $user->reports();

        $myLostIds = $user->reports()->where('report_type', 'lost')->pluck('id');
        $matches = ImageMatchResult::query()
            ->whereIn('lost_report_id', $myLostIds)
            ->where('match_status', '!=', MatchStatus::Dismissed)
            ->whereHas('foundReport', fn ($q) => $q->where('report_status', ReportStatus::Published))
            ->with(['lostReport', 'foundReport.primaryImage'])
            ->orderByDesc('similarity_score')->limit(5)->get();

        return view('dashboard', [
            'counts' => [
                'total' => (clone $reports)->count(),
                'listed' => (clone $reports)->where('report_status', ReportStatus::Published)->count(),
                'pending' => (clone $reports)->where('report_status', ReportStatus::PendingReview)->count(),
                'resolved' => (clone $reports)->where('report_status', ReportStatus::Resolved)->count(),
                'open_claims' => $user->claims()->whereIn('status', [ClaimStatus::Pending, ClaimStatus::UnderReview])->count(),
            ],
            'recentReports' => $user->reports()->with(['category', 'primaryImage'])->latest()->limit(5)->get(),
            'claims' => $user->claims()->with('foundReport')->latest()->limit(5)->get(),
            'matches' => $matches,
        ]);
    }
}
