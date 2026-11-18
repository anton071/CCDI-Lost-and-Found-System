<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ItemReport;
use App\Services\ClaimException;
use App\Services\ImageMatchingService;
use App\Services\ReportModerationService;
use App\Services\TextMatchService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(['lost', 'found'])],
            'status' => ['nullable', Rule::enum(ReportStatus::class)],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        return view('admin.reports.index', [
            'reports' => ItemReport::filter($filters)->with(['category', 'submitter', 'primaryImage'])->latest('id')->paginate(15)->withQueryString(),
            'filters' => $filters,
            'categories' => Category::orderBy('name')->get(),
            'statuses' => ReportStatus::cases(),
        ]);
    }

    public function show(ItemReport $report, TextMatchService $text)
    {
        $report->load(['category', 'images', 'submitter', 'reviewer', 'claims.claimant']);
        $matches = ($report->isLost() ? $report->matchesAsLost() : $report->matchesAsFound())
            ->with(['lostReport.primaryImage', 'foundReport.primaryImage'])->orderByDesc('similarity_score')->get();

        return view('admin.reports.show', [
            'report' => $report,
            'matches' => $matches,
            'textMatches' => $text->suggest($report),
            'transitions' => $report->report_status->allowedNext(),
        ]);
    }

    public function status(Request $request, ItemReport $report, ReportModerationService $service)
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(ReportStatus::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            [, $outcome] = $service->transition($report, ReportStatus::from($data['status']), $request->user(), $data['notes'] ?? null);
        } catch (ClaimException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Report status updated.')
            ->with('match_outcome', $outcome ? ['status' => $outcome->status, 'message' => $outcome->message] : null);
    }

    public function rematch(ItemReport $report, ImageMatchingService $matcher)
    {
        $outcome = $matcher->matchReport($report);

        return back()->with('match_outcome', ['status' => $outcome->status, 'message' => $outcome->message]);
    }
}
