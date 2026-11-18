<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClaimStatus;
use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\ImageMatchResult;
use App\Services\ClaimException;
use App\Services\ClaimService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClaimController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->validate(['status' => ['nullable', Rule::enum(ClaimStatus::class)]])['status'] ?? null;

        return view('admin.claims.index', [
            'claims' => Claim::with(['foundReport', 'claimant'])
                ->when($status, fn ($q) => $q->where('status', $status))
                ->latest('id')->paginate(15)->withQueryString(),
            'status' => $status,
            'statuses' => ClaimStatus::cases(),
        ]);
    }

    public function show(Claim $claim)
    {
        $claim->load(['foundReport.images', 'foundReport.category', 'foundReport.submitter', 'relatedLostReport.images', 'claimant', 'reviewer', 'release.releasedBy']);

        $related = ImageMatchResult::query()
            ->where('found_report_id', $claim->found_report_id)
            ->when($claim->related_lost_report_id, fn ($q) => $q->where('lost_report_id', $claim->related_lost_report_id))
            ->with('lostReport')->orderByDesc('similarity_score')->limit(5)->get();

        return view('admin.claims.show', [
            'claim' => $claim,
            'matches' => $related,
            'canAct' => auth()->user()->can('review', $claim),
        ]);
    }

    public function underReview(Claim $claim, ClaimService $service, Request $request)
    {
        return $this->run($claim, $request, fn () => $service->markUnderReview($claim, $request->user()), 'Claim marked as under review.');
    }

    public function approve(Claim $claim, ClaimService $service, Request $request)
    {
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);

        return $this->run($claim, $request, fn () => $service->approve($claim, $request->user(), $data['notes'] ?? null), 'Claim approved. Record the handover once the item is physically released.');
    }

    public function reject(Claim $claim, ClaimService $service, Request $request)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);

        return $this->run($claim, $request, fn () => $service->reject($claim, $request->user(), $data['reason']), 'Claim rejected.');
    }

    public function release(Claim $claim, ClaimService $service, Request $request)
    {
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);

        return $this->run($claim, $request, fn () => $service->release($claim, $request->user(), $data['notes'] ?? null), 'Item release recorded.');
    }

    private function run(Claim $claim, Request $request, \Closure $action, string $success)
    {
        $this->authorize('review', $claim);

        try {
            $action();
        } catch (ClaimException $e) {
            return back()->withErrors(['claim' => $e->getMessage()]);
        }

        return back()->with('status', $success);
    }
}
