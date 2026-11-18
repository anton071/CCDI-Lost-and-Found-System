<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MatchStatus;
use App\Http\Controllers\Controller;
use App\Models\ImageMatchResult;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MatchController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->validate(['status' => ['nullable', Rule::enum(MatchStatus::class)]])['status'] ?? null;

        return view('admin.matches.index', [
            'matches' => ImageMatchResult::with(['lostReport.primaryImage', 'foundReport.primaryImage'])
                ->when($status, fn ($q) => $q->where('match_status', $status))
                ->orderByDesc('similarity_score')->paginate(12)->withQueryString(),
            'status' => $status,
            'statuses' => MatchStatus::cases(),
        ]);
    }

    public function update(Request $request, ImageMatchResult $match)
    {
        $data = $request->validate([
            'match_status' => ['required', Rule::in([MatchStatus::Reviewed->value, MatchStatus::Dismissed->value, MatchStatus::ConfirmedMatch->value])],
        ]);

        $match->match_status = MatchStatus::from($data['match_status']);
        $match->reviewed_by = $request->user()->id;
        $match->reviewed_at = now();
        $match->save();

        ActivityLogger::log('match.reviewed', $match, "Potential match #{$match->id} marked {$match->match_status->label()}.", [], $request->user());

        return back()->with('status', 'Potential match updated. Ownership still has to be verified through a claim.');
    }
}
