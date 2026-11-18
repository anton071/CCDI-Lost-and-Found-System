<?php

namespace App\Http\Controllers;

use App\Enums\ClaimStatus;
use App\Enums\MatchStatus;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Http\Requests\StoreReportRequest;
use App\Models\Category;
use App\Models\ItemReport;
use App\Services\ReportService;
use App\Services\TextMatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class ReportController extends Controller
{
    public function create(string $type)
    {
        $this->authorize('create', ItemReport::class);

        return view('reports.create', [
            'type' => ReportType::from($type),
            'categories' => Category::active()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreReportRequest $request, string $type, ReportService $service)
    {
        $type = ReportType::from($type);

        try {
            [$report, $outcome] = $service->create(
                $request->user(),
                $type,
                collect($request->validated())->except('photo')->all(),
                $request->file('photo'),
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['photo' => $e->getMessage()]);
        }

        $notice = $report->report_status === ReportStatus::PendingReview
            ? 'Your report was submitted and is waiting for administrator review before it is listed.'
            : 'Your report was submitted and is now listed.';

        return redirect()->route('reports.show', $report)
            ->with('status', $notice)
            ->with('match_outcome', $outcome ? ['status' => $outcome->status, 'message' => $outcome->message] : null);
    }

    public function show(Request $request, ItemReport $report, TextMatchService $text)
    {
        abort_unless(Gate::forUser($request->user())->allows('view', $report), 404);
        $user = $request->user();

        $report->load(['category', 'images', 'primaryImage', 'submitter']);

        $canSeePrivate = $user && Gate::forUser($user)->allows('viewPrivate', $report);
        $canSeeMatches = $user && Gate::forUser($user)->allows('viewMatches', $report);

        $imageMatches = collect();
        $textMatches = collect();
        if ($canSeeMatches) {
            $relation = $report->isLost() ? $report->matchesAsLost() : $report->matchesAsFound();
            $imageMatches = $relation->where('match_status', '!=', MatchStatus::Dismissed)
                ->with(['lostReport.primaryImage', 'lostReport.category', 'foundReport.primaryImage', 'foundReport.category'])
                ->orderByDesc('similarity_score')->get()
                ->filter(fn ($m) => $user->isAdmin() || $m->foundReport->report_status === ReportStatus::Published);
            $textMatches = $text->suggest($report);
        }

        $myLostReports = collect();
        $myOpenClaim = null;
        $canClaim = $user && Gate::forUser($user)->allows('claim', $report);
        if ($canClaim) {
            $myLostReports = $user->reports()->where('report_type', ReportType::Lost)->where('report_status', ReportStatus::Published)->get(['id', 'item_name', 'reference_code']);
            $myOpenClaim = $report->claims()->where('claimant_user_id', $user->id)->whereIn('status', ClaimStatus::open())->first();
        }

        return view('reports.show', compact('report', 'canSeePrivate', 'imageMatches', 'textMatches', 'canClaim', 'myLostReports', 'myOpenClaim'));
    }

    public function mine(Request $request)
    {
        return view('reports.mine', [
            'reports' => $request->user()->reports()->with(['category', 'primaryImage'])->latest()->paginate(10),
        ]);
    }
}
