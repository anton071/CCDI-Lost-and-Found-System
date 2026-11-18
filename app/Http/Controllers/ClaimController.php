<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClaimRequest;
use App\Models\Claim;
use App\Models\ItemReport;
use App\Services\ClaimException;
use App\Services\ClaimService;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    public function index(Request $request)
    {
        return view('claims.index', [
            'claims' => $request->user()->claims()->with('foundReport.primaryImage')->latest()->paginate(10),
        ]);
    }

    public function store(StoreClaimRequest $request, ItemReport $report, ClaimService $service)
    {
        $this->authorize('claim', $report);

        try {
            $claim = $service->submit($request->user(), $report, $request->validated());
        } catch (ClaimException $e) {
            return back()->withInput()->withErrors(['claim_message' => $e->getMessage()]);
        }

        return redirect()->route('claims.show', $claim)
            ->with('status', 'Your claim was submitted. An administrator will verify it before any item is released.');
    }

    public function show(Claim $claim)
    {
        $this->authorize('view', $claim);

        return view('claims.show', ['claim' => $claim->load(['foundReport.primaryImage', 'foundReport.category', 'release', 'reviewer'])]);
    }

    public function cancel(Claim $claim, ClaimService $service, Request $request)
    {
        $this->authorize('cancel', $claim);

        try {
            $service->cancel($claim, $request->user());
        } catch (ClaimException $e) {
            return back()->withErrors(['claim' => $e->getMessage()]);
        }

        return redirect()->route('claims.index')->with('status', 'Your claim was cancelled.');
    }
}
