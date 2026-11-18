<?php

namespace Tests\Feature;

use App\Enums\ClaimStatus;
use App\Enums\ReportStatus;
use App\Models\Claim;
use App\Models\ItemRelease;
use App\Models\ItemReport;
use App\Models\User;
use App\Notifications\ClaimStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ClaimTest extends TestCase
{
    use RefreshDatabase;

    private string $msg = 'It has a blue sticker and a scratch on the left side; I lost it on Monday.';

    private function found(array $over = []): ItemReport
    {
        return ItemReport::factory()->found()->create($over);
    }

    // ---- submitting --------------------------------------------------------------------

    public function test_member_can_submit_a_claim_which_starts_pending(): void
    {
        $found = $this->found();
        $user = $this->member();
        $this->actingAs($user)->post("/reports/{$found->id}/claims", ['claim_message' => $this->msg])->assertRedirect();
        $claim = Claim::firstOrFail();
        $this->assertSame(ClaimStatus::Pending, $claim->status);
        $this->assertSame($user->id, $claim->claimant_user_id);
        $this->assertSame($found->id, $claim->found_report_id);
        $this->assertDatabaseHas('activity_logs', ['action' => 'claim.submitted', 'subject_id' => $claim->id]);
    }

    public function test_claim_requires_a_meaningful_message_and_login(): void
    {
        $found = $this->found();
        $this->actingAs($this->member())->post("/reports/{$found->id}/claims", ['claim_message' => 'mine'])->assertSessionHasErrors('claim_message');
        auth()->logout();
        $this->post("/reports/{$found->id}/claims", ['claim_message' => $this->msg])->assertRedirect('/login');
        $this->assertSame(0, Claim::count());
    }

    public function test_status_cannot_be_injected_through_the_claim_form(): void
    {
        $found = $this->found();
        $u = $this->member();
        $this->actingAs($u)->post("/reports/{$found->id}/claims", ['claim_message' => $this->msg, 'status' => 'approved', 'claimant_user_id' => 999, 'reviewed_by' => $u->id]);
        $c = Claim::firstOrFail();
        $this->assertSame(ClaimStatus::Pending, $c->status);
        $this->assertSame($u->id, $c->claimant_user_id);
        $this->assertNull($c->reviewed_by);
    }

    public function test_cannot_claim_unpublished_lost_or_own_found_items(): void
    {
        $u = $this->member();
        $pending = $this->found(['report_status' => ReportStatus::PendingReview]);
        $lost = ItemReport::factory()->lost()->create();
        $own = $this->found(['submitted_by' => $u->id]);
        foreach ([$pending, $lost, $own] as $r) {
            $this->actingAs($u)->post("/reports/{$r->id}/claims", ['claim_message' => $this->msg])->assertForbidden();
        }
        $this->assertSame(0, Claim::count());
    }

    public function test_duplicate_open_claims_by_the_same_user_are_refused(): void
    {
        $found = $this->found();
        $u = $this->member();
        $this->actingAs($u)->post("/reports/{$found->id}/claims", ['claim_message' => $this->msg]);
        $this->actingAs($u)->post("/reports/{$found->id}/claims", ['claim_message' => $this->msg])->assertSessionHasErrors('claim_message');
        $this->assertSame(1, Claim::count());
    }

    public function test_new_claims_are_blocked_once_one_is_approved(): void
    {
        $found = $this->found();
        Claim::factory()->status(ClaimStatus::Approved)->create(['found_report_id' => $found->id]);
        $this->actingAs($this->member())->post("/reports/{$found->id}/claims", ['claim_message' => $this->msg])->assertSessionHasErrors('claim_message');
        $this->assertSame(1, Claim::count());
    }

    public function test_cannot_link_someone_elses_lost_report_to_a_claim(): void
    {
        $found = $this->found();
        $others = ItemReport::factory()->lost()->create();
        $this->actingAs($this->member())->post("/reports/{$found->id}/claims", ['claim_message' => $this->msg, 'related_lost_report_id' => $others->id])->assertSessionHasErrors('claim_message');
        $this->assertSame(0, Claim::count());
    }

    public function test_claimant_can_cancel_an_open_claim_but_not_a_decided_one(): void
    {
        $claim = Claim::factory()->create();
        $this->actingAs($claim->claimant)->post("/claims/{$claim->id}/cancel")->assertRedirect('/claims');
        $this->assertSame(ClaimStatus::Cancelled, $claim->fresh()->status);

        $done = Claim::factory()->status(ClaimStatus::Rejected)->create();
        $this->actingAs($done->claimant)->post("/claims/{$done->id}/cancel")->assertForbidden();
    }

    // ---- admin decisions ---------------------------------------------------------------

    public function test_non_admin_cannot_approve_reject_or_release(): void
    {
        $claim = Claim::factory()->create();
        $u = $this->member();
        foreach (['approve', 'reject', 'release', 'under-review'] as $a) {
            $this->actingAs($u)->post("/admin/claims/{$claim->id}/{$a}", ['reason' => 'nope nope'])->assertForbidden();
        }
        $this->actingAs($claim->claimant)->post("/admin/claims/{$claim->id}/approve")->assertForbidden();   // claimant cannot approve own claim
        $this->assertSame(ClaimStatus::Pending, $claim->fresh()->status);
    }

    public function test_admin_cannot_approve_or_release_their_own_claim(): void
    {
        $admin = $this->admin();
        $claim = Claim::factory()->create(['claimant_user_id' => $admin->id]);
        $this->actingAs($admin)->post("/admin/claims/{$claim->id}/approve")->assertForbidden();
        $this->assertSame(ClaimStatus::Pending, $claim->fresh()->status);

        $approved = Claim::factory()->status(ClaimStatus::Approved)->create(['claimant_user_id' => $admin->id]);
        $this->actingAs($admin)->post("/admin/claims/{$approved->id}/release")->assertForbidden();
        $this->assertSame(0, ItemRelease::count());
    }

    public function test_admin_can_mark_under_review_then_approve_with_notes(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $claim = Claim::factory()->create();
        $this->actingAs($admin)->post("/admin/claims/{$claim->id}/under-review")->assertSessionHasNoErrors();
        $this->assertSame(ClaimStatus::UnderReview, $claim->fresh()->status);
        $this->actingAs($admin)->post("/admin/claims/{$claim->id}/approve", ['notes' => 'ID and serial verified'])->assertSessionHasNoErrors();
        $claim->refresh();
        $this->assertSame(ClaimStatus::Approved, $claim->status);
        $this->assertSame($admin->id, $claim->reviewed_by);
        $this->assertSame('ID and serial verified', $claim->review_notes);
        $this->assertNotNull($claim->reviewed_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'claim.approved', 'subject_id' => $claim->id, 'user_id' => $admin->id]);
        Notification::assertSentTo($claim->claimant, ClaimStatusChanged::class);
        $this->assertSame(ReportStatus::Published, $claim->foundReport->fresh()->report_status, 'approval alone must not close the report');
    }

    public function test_rejection_requires_a_reason(): void
    {
        $claim = Claim::factory()->create();
        $this->actingAs($this->admin())->post("/admin/claims/{$claim->id}/reject", [])->assertSessionHasErrors('reason');
        $this->assertSame(ClaimStatus::Pending, $claim->fresh()->status);
        $this->actingAs($this->admin())->post("/admin/claims/{$claim->id}/reject", ['reason' => 'Description does not match the item.']);
        $claim->refresh();
        $this->assertSame(ClaimStatus::Rejected, $claim->status);
        $this->assertSame('Description does not match the item.', $claim->review_notes);
    }

    public function test_decided_claims_cannot_change_state_again(): void
    {
        $admin = $this->admin();
        $rejected = Claim::factory()->status(ClaimStatus::Rejected)->create();
        $this->actingAs($admin)->post("/admin/claims/{$rejected->id}/approve")->assertSessionHasErrors('claim');
        $this->assertSame(ClaimStatus::Rejected, $rejected->fresh()->status);

        $cancelled = Claim::factory()->status(ClaimStatus::Cancelled)->create();
        $this->actingAs($admin)->post("/admin/claims/{$cancelled->id}/approve")->assertSessionHasErrors('claim');
    }

    public function test_only_one_claim_per_item_can_be_approved(): void
    {
        $found = $this->found();
        $a = Claim::factory()->create(['found_report_id' => $found->id]);
        $b = Claim::factory()->create(['found_report_id' => $found->id]);
        $admin = $this->admin();
        $this->actingAs($admin)->post("/admin/claims/{$a->id}/approve")->assertSessionHasNoErrors();
        $this->actingAs($admin)->post("/admin/claims/{$b->id}/approve")->assertSessionHasErrors('claim');
        $this->assertSame(ClaimStatus::Pending, $b->fresh()->status);
    }

    // ---- release -----------------------------------------------------------------------

    public function test_release_requires_an_approved_claim(): void
    {
        $admin = $this->admin();
        $claim = Claim::factory()->create();   // pending
        $this->actingAs($admin)->post("/admin/claims/{$claim->id}/release")->assertSessionHasErrors('claim');
        $this->assertSame(0, ItemRelease::count());
        $this->assertSame(ReportStatus::Published, $claim->foundReport->fresh()->report_status);
    }

    public function test_release_updates_report_claim_history_and_closes_competing_claims(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $found = $this->found();
        $winner = Claim::factory()->status(ClaimStatus::Approved)->create(['found_report_id' => $found->id]);
        $lostReport = ItemReport::factory()->lost()->create(['submitted_by' => $winner->claimant_user_id]);
        $winner->update(['related_lost_report_id' => $lostReport->id]);
        $rival = Claim::factory()->create(['found_report_id' => $found->id]);

        $this->actingAs($admin)->post("/admin/claims/{$winner->id}/release", ['notes' => 'Checked school ID'])->assertSessionHasNoErrors();

        $release = ItemRelease::firstOrFail();
        $this->assertSame($winner->id, $release->claim_id);
        $this->assertSame($winner->claimant_user_id, $release->released_to_user_id);
        $this->assertSame($admin->id, $release->released_by);
        $this->assertMatchesRegularExpression('/^REL-\d{6}-[A-Z0-9]{5}$/', $release->release_reference);
        $found->refresh();
        $this->assertSame(ReportStatus::Resolved, $found->report_status);
        $this->assertNotNull($found->resolved_at);
        $this->assertSame(ReportStatus::Resolved, $lostReport->fresh()->report_status);
        $rival->refresh();
        $this->assertSame(ClaimStatus::Rejected, $rival->status);
        $this->assertStringContainsString('released to another', $rival->review_notes);
        $this->assertDatabaseHas('activity_logs', ['action' => 'item.released', 'user_id' => $admin->id]);
        Notification::assertSentTo($winner->claimant, ClaimStatusChanged::class);
    }

    public function test_an_item_cannot_be_released_twice(): void
    {
        $admin = $this->admin();
        $claim = Claim::factory()->status(ClaimStatus::Approved)->create();
        $this->actingAs($admin)->post("/admin/claims/{$claim->id}/release")->assertSessionHasNoErrors();
        $this->actingAs($admin)->post("/admin/claims/{$claim->id}/release")->assertSessionHasErrors('claim');
        $this->assertSame(1, ItemRelease::count());
    }

    public function test_released_items_leave_the_public_listing_and_cannot_be_claimed(): void
    {
        $admin = $this->admin();
        $found = $this->found(['item_name' => 'Gone soon bag']);
        $claim = Claim::factory()->status(ClaimStatus::Approved)->create(['found_report_id' => $found->id]);
        $this->actingAs($admin)->post("/admin/claims/{$claim->id}/release");
        $this->get('/items')->assertDontSee('Gone soon bag');
        $this->actingAs($this->member())->post("/reports/{$found->id}/claims", ['claim_message' => $this->msg])->assertForbidden();
    }

    public function test_claim_pages_render_for_claimant_and_admin(): void
    {
        $claim = Claim::factory()->create();
        $this->actingAs($claim->claimant)->get("/claims/{$claim->id}")->assertOk()->assertSee('Waiting for an administrator');
        $this->actingAs($claim->claimant)->get('/claims')->assertOk();
        $this->actingAs($this->admin())->get("/admin/claims/{$claim->id}")->assertOk()->assertSee('Private verification details');
    }

    public function test_every_decision_path_leaves_an_audit_trail(): void
    {
        $admin = $this->admin();
        $claim = Claim::factory()->create();
        $this->actingAs($admin)->post("/admin/claims/{$claim->id}/under-review");
        $this->actingAs($admin)->post("/admin/claims/{$claim->id}/approve");
        $this->actingAs($admin)->post("/admin/claims/{$claim->id}/release");
        foreach (['claim.under_review', 'claim.approved', 'item.released'] as $action) {
            $this->assertDatabaseHas('activity_logs', ['action' => $action, 'user_id' => $admin->id]);
        }
    }
}
