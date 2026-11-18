<?php

namespace App\Services;

use App\Enums\ClaimStatus;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\Claim;
use App\Models\ItemRelease;
use App\Models\ItemReport;
use App\Models\User;
use App\Notifications\ClaimStatusChanged;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
 * Claim workflow:  pending → under_review → approved → (released)   |   rejected / cancelled
 * Every state change runs in a transaction with row locks, is re-checked on the server, is logged
 * to activity_logs, and notifies the affected people. An image match is never an input here.
 */
class ClaimService
{
    public function submit(User $user, ItemReport $found, array $data): Claim
    {
        $claim = DB::transaction(function () use ($user, $found, $data) {
            $found = ItemReport::whereKey($found->id)->lockForUpdate()->firstOrFail();

            if ($found->report_type !== ReportType::Found || $found->report_status !== ReportStatus::Published) {
                throw new ClaimException('This item is not open for claims.');
            }
            if ($found->submitted_by === $user->id) {
                throw new ClaimException('You cannot claim an item that you reported as found.');
            }
            if ($found->claims()->where('status', ClaimStatus::Approved)->exists()) {
                throw new ClaimException('A claim for this item has already been approved. Please contact the administrator.');
            }
            if ($found->claims()->where('claimant_user_id', $user->id)->whereIn('status', ClaimStatus::open())->exists()) {
                throw new ClaimException('You already have an open claim for this item.');
            }

            $related = $data['related_lost_report_id'] ?? null;
            if ($related) {
                $owns = ItemReport::whereKey($related)->where('submitted_by', $user->id)->where('report_type', ReportType::Lost)->exists();
                if (! $owns) {
                    throw new ClaimException('The selected lost report is not yours.');
                }
            }

            $claim = new Claim(['claim_message' => $data['claim_message'], 'related_lost_report_id' => $related]);
            $claim->found_report_id = $found->id;
            $claim->claimant_user_id = $user->id;
            $claim->status = ClaimStatus::Pending;
            $claim->save();

            ActivityLogger::log('claim.submitted', $claim, "Claim #{$claim->id} submitted for {$found->reference_code}.", [], $user);

            return $claim;
        });

        User::query()->where('role', 'admin')->where('is_active', true)->get()->each(
            fn (User $a) => $a->notify(new ClaimStatusChanged($claim, 'New claim awaiting review', "Claim #{$claim->id} was submitted.", true))
        );

        return $claim;
    }

    public function markUnderReview(Claim $claim, User $admin): Claim
    {
        return $this->review($claim, $admin, ClaimStatus::UnderReview, null, 'Your claim is being reviewed');
    }

    public function approve(Claim $claim, User $admin, ?string $notes = null): Claim
    {
        return $this->review($claim, $admin, ClaimStatus::Approved, $notes, 'Your claim was approved', 'Visit the lost-and-found office with a valid ID to receive the item.');
    }

    public function reject(Claim $claim, User $admin, string $reason): Claim
    {
        if (blank($reason)) {
            throw new ClaimException('A reason is required when rejecting a claim.');
        }

        return $this->review($claim, $admin, ClaimStatus::Rejected, $reason, 'Your claim was not approved', $reason);
    }

    public function cancel(Claim $claim, User $actor): Claim
    {
        $isOwner = $claim->claimant_user_id === $actor->id;
        if (! $isOwner && ! $actor->isAdmin()) {
            throw new AuthorizationException('You cannot cancel this claim.');
        }

        return DB::transaction(function () use ($claim, $actor) {
            $claim = Claim::whereKey($claim->id)->lockForUpdate()->firstOrFail();
            $this->guardTransition($claim, ClaimStatus::Cancelled);
            $claim->status = ClaimStatus::Cancelled;
            $claim->save();
            ActivityLogger::log('claim.cancelled', $claim, "Claim #{$claim->id} cancelled.", [], $actor);

            return $claim;
        });
    }

    public function release(Claim $claim, User $admin, ?string $notes = null): ItemRelease
    {
        $this->assertAdmin($admin);

        $release = DB::transaction(function () use ($claim, $admin, $notes) {
            $claim = Claim::whereKey($claim->id)->lockForUpdate()->firstOrFail();
            $found = ItemReport::whereKey($claim->found_report_id)->lockForUpdate()->firstOrFail();

            if ($claim->claimant_user_id === $admin->id) {
                throw new ClaimException('Administrators cannot release an item to themselves.');
            }
            if ($claim->status !== ClaimStatus::Approved) {
                throw new ClaimException('Only an approved claim can be released.');
            }
            if ($claim->release()->exists() || $found->report_status !== ReportStatus::Published) {
                throw new ClaimException('This item has already been released or is no longer available.');
            }

            $release = ItemRelease::create([
                'claim_id' => $claim->id,
                'found_report_id' => $found->id,
                'released_by' => $admin->id,
                'released_to_user_id' => $claim->claimant_user_id,
                'release_reference' => 'REL-'.now()->format('ymd').'-'.strtoupper(Str::random(5)),
                'verification_notes' => $notes,
                'released_at' => now(),
            ]);

            $found->report_status = ReportStatus::Resolved;
            $found->resolved_at = now();
            $found->save();

            if ($claim->related_lost_report_id) {
                ItemReport::whereKey($claim->related_lost_report_id)
                    ->where('submitted_by', $claim->claimant_user_id)
                    ->where('report_status', ReportStatus::Published)
                    ->update(['report_status' => ReportStatus::Resolved, 'resolved_at' => now()]);
            }

            // Competing open claims can no longer be satisfied.
            Claim::where('found_report_id', $found->id)->whereKeyNot($claim->id)
                ->whereIn('status', [ClaimStatus::Pending, ClaimStatus::UnderReview])
                ->update([
                    'status' => ClaimStatus::Rejected,
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                    'review_notes' => 'The item was released to another verified claimant.',
                ]);

            ActivityLogger::log('item.released', $release, "Item {$found->reference_code} released to user #{$claim->claimant_user_id}.", ['claim_id' => $claim->id], $admin);

            return $release;
        });

        $claim->claimant->notify(new ClaimStatusChanged($claim, 'Your item has been released', "Release reference {$release->release_reference}."));

        return $release;
    }

    private function review(Claim $claim, User $admin, ClaimStatus $to, ?string $notes, string $headline, ?string $detail = null): Claim
    {
        $this->assertAdmin($admin);

        $claim = DB::transaction(function () use ($claim, $admin, $to, $notes) {
            $claim = Claim::whereKey($claim->id)->lockForUpdate()->firstOrFail();

            if ($claim->claimant_user_id === $admin->id) {
                throw new ClaimException('Administrators cannot review their own claims.');
            }
            $this->guardTransition($claim, $to);

            if ($to === ClaimStatus::Approved) {
                ItemReport::whereKey($claim->found_report_id)->lockForUpdate()->first();
                $other = Claim::where('found_report_id', $claim->found_report_id)->whereKeyNot($claim->id)
                    ->where('status', ClaimStatus::Approved)->exists();
                if ($other) {
                    throw new ClaimException('Another claim for this item is already approved.');
                }
            }

            $from = $claim->status;
            $claim->status = $to;
            $claim->reviewed_by = $admin->id;
            $claim->reviewed_at = now();
            $claim->review_notes = $notes ?: $claim->review_notes;
            $claim->save();

            ActivityLogger::log('claim.'.$to->value, $claim, "Claim #{$claim->id}: {$from->label()} → {$to->label()}.", ['notes' => $notes], $admin);

            return $claim;
        });

        $claim->claimant->notify(new ClaimStatusChanged($claim, $headline, $detail));

        return $claim;
    }

    private function guardTransition(Claim $claim, ClaimStatus $to): void
    {
        if (! $claim->status->canTransitionTo($to)) {
            throw new ClaimException("A {$claim->status->label()} claim cannot be changed to {$to->label()}.");
        }
    }

    private function assertAdmin(User $user): void
    {
        if (! $user->isAdmin()) {
            throw new AuthorizationException('Only administrators can do this.');
        }
    }
}
