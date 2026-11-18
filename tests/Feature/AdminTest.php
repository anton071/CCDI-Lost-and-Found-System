<?php

namespace Tests\Feature;

use App\Enums\ClaimStatus;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\Claim;
use App\Models\ImageMatchResult;
use App\Models\ItemRelease;
use App\Models\ItemReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_empty_state_numbers_when_there_is_no_data(): void
    {
        $res = $this->actingAs($this->admin())->get('/admin')->assertOk();
        $res->assertSee('Nothing is waiting for review.')->assertSee('No open claims.')->assertSee('No activity recorded yet.');
        $stats = $res->viewData('stats');
        $this->assertSame(['lost' => 0, 'found' => 0, 'pending_claims' => 0, 'unresolved' => 0, 'returned' => 0], $stats);
    }

    public function test_dashboard_statistics_come_from_real_records(): void
    {
        ItemReport::factory()->lost()->count(3)->create();                       // published lost
        ItemReport::factory()->lost()->pending()->create();                      // pending lost (still counted as open)
        ItemReport::factory()->found()->count(2)->create();                      // published found
        ItemReport::factory()->found()->pending()->count(4)->create();           // awaiting review
        ItemReport::factory()->found()->status(ReportStatus::Resolved)->create();
        ItemReport::factory()->lost()->status(ReportStatus::Archived)->create();
        Claim::factory()->count(2)->create();                                    // pending
        Claim::factory()->status(ClaimStatus::UnderReview)->create();
        Claim::factory()->status(ClaimStatus::Rejected)->create();
        $claim = Claim::factory()->status(ClaimStatus::Approved)->create();
        ItemRelease::create(['claim_id' => $claim->id, 'found_report_id' => $claim->found_report_id, 'released_to_user_id' => $claim->claimant_user_id, 'released_at' => now()]);

        $res = $this->actingAs($this->admin())->get('/admin')->assertOk();
        $s = $res->viewData('stats');
        $t = $res->viewData('tasks');
        // Each of the 5 claims created above also creates one published found report.
        $this->assertSame(4, $s['lost']);
        $this->assertSame(2 + 4 + 5, $s['found']);
        $this->assertSame(3, $s['pending_claims']);
        $this->assertSame(1, $s['returned']);
        $this->assertSame(5, $t['reports']);   // 4 found + 1 lost awaiting review
        $this->assertSame(3, $t['claims']);
    }

    public function test_admin_publishes_a_pending_found_report_and_it_becomes_public(): void
    {
        $admin = $this->admin();
        $r = ItemReport::factory()->found()->pending()->create(['item_name' => 'Waiting wallet']);
        $this->get('/items')->assertDontSee('Waiting wallet');
        $this->actingAs($admin)->post("/admin/reports/{$r->id}/status", ['status' => 'published'])->assertSessionHasNoErrors();
        $r->refresh();
        $this->assertSame(ReportStatus::Published, $r->report_status);
        $this->assertSame($admin->id, $r->reviewed_by);
        $this->get('/items')->assertSee('Waiting wallet');
        $this->assertDatabaseHas('activity_logs', ['action' => 'report.published', 'subject_id' => $r->id]);
    }

    public function test_rejecting_a_report_needs_a_reason_and_notifies_the_reporter(): void
    {
        $r = ItemReport::factory()->found()->pending()->create();
        $admin = $this->admin();
        $this->actingAs($admin)->post("/admin/reports/{$r->id}/status", ['status' => 'rejected'])->assertSessionHasErrors('status');
        $this->actingAs($admin)->post("/admin/reports/{$r->id}/status", ['status' => 'rejected', 'notes' => 'Duplicate of another report']);
        $this->assertSame('Duplicate of another report', $r->fresh()->rejection_reason);
        $this->assertSame(1, $r->submitter->notifications()->count());
    }

    public function test_invalid_report_status_transitions_are_refused(): void
    {
        $admin = $this->admin();
        $archived = ItemReport::factory()->status(ReportStatus::Archived)->create();
        $this->actingAs($admin)->post("/admin/reports/{$archived->id}/status", ['status' => 'published'])->assertSessionHasErrors('status');
        $pending = ItemReport::factory()->pending()->create();
        $this->actingAs($admin)->post("/admin/reports/{$pending->id}/status", ['status' => 'resolved'])->assertSessionHasErrors('status');
        $this->actingAs($admin)->post("/admin/reports/{$pending->id}/status", ['status' => 'bogus'])->assertSessionHasErrors('status');
        $this->assertSame(ReportStatus::Archived, $archived->fresh()->report_status);
        $this->assertSame(ReportStatus::PendingReview, $pending->fresh()->report_status);
    }

    public function test_admin_lists_filter_and_show_pages_render(): void
    {
        $admin = $this->admin();
        $lost = ItemReport::factory()->lost()->create(['item_name' => 'Listed lost thing']);
        $found = ItemReport::factory()->found()->pending()->create(['item_name' => 'Hidden found thing']);
        $this->actingAs($admin)->get('/admin/reports?status=pending_review')->assertSee('Hidden found thing')->assertDontSee('Listed lost thing');
        $this->actingAs($admin)->get('/admin/reports?q=lost+thing')->assertSee('Listed lost thing');
        $this->actingAs($admin)->get("/admin/reports/{$lost->id}")->assertOk()->assertSee('Private identifying details');
        $this->actingAs($admin)->get('/admin/reports?status=bogus')->assertSessionHasErrors('status');
    }

    public function test_matches_can_be_reviewed_but_never_grant_ownership(): void
    {
        $admin = $this->admin();
        $lost = ItemReport::factory()->lost()->create();
        $found = ItemReport::factory()->found()->create();
        $m = ImageMatchResult::create(['lost_report_id' => $lost->id, 'found_report_id' => $found->id, 'algorithm_used' => 'SIFT', 'similarity_score' => 88, 'good_matches' => 90]);
        $this->actingAs($admin)->post("/admin/matches/{$m->id}", ['match_status' => 'confirmed_match'])->assertSessionHasNoErrors();
        $this->assertSame('confirmed_match', $m->fresh()->match_status->value);
        $this->assertSame(ReportStatus::Published, $found->fresh()->report_status);
        $this->assertSame(0, Claim::count());
        $this->assertSame(0, ItemRelease::count());
        $this->actingAs($admin)->post("/admin/matches/{$m->id}", ['match_status' => 'pending_review'])->assertSessionHasErrors('match_status');
        $this->actingAs($this->member())->post("/admin/matches/{$m->id}", ['match_status' => 'dismissed'])->assertForbidden();
    }

    public function test_user_management_changes_roles_and_active_state_with_a_log_entry(): void
    {
        $admin = $this->admin();
        $u = $this->member();
        $this->actingAs($admin)->patch("/admin/users/{$u->id}", ['role' => 'teacher', 'is_active' => 0])->assertSessionHasNoErrors();
        $u->refresh();
        $this->assertSame(UserRole::Teacher, $u->role);
        $this->assertFalse($u->is_active);
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.access_changed', 'subject_id' => $u->id, 'user_id' => $admin->id]);
    }

    public function test_admin_cannot_demote_or_deactivate_themselves_or_the_last_admin(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->patch("/admin/users/{$admin->id}", ['role' => 'student', 'is_active' => 1])->assertSessionHasErrors('user');
        $this->actingAs($admin)->patch("/admin/users/{$admin->id}", ['role' => 'admin', 'is_active' => 0])->assertSessionHasErrors('user');
        $this->assertTrue($admin->fresh()->isAdmin());

        $other = $this->admin();
        $this->actingAs($admin)->patch("/admin/users/{$other->id}", ['role' => 'student', 'is_active' => 1])->assertSessionHasNoErrors();   // two admins: allowed
        $this->assertFalse($other->fresh()->isAdmin());

        $third = $this->admin();
        $this->actingAs($third)->patch("/admin/users/{$admin->id}", ['role' => 'student', 'is_active' => 1])->assertSessionHasNoErrors();
        $this->actingAs($third)->patch("/admin/users/{$third->id}", ['role' => 'student', 'is_active' => 1])->assertSessionHasErrors('user');
        $this->assertSame(1, User::where('role', UserRole::Admin)->count());
    }

    public function test_deactivated_admin_loses_admin_access(): void
    {
        $admin = $this->admin();
        $admin->forceFill(['is_active' => false])->save();
        $this->actingAs($admin)->get('/admin')->assertRedirect('/login');
    }

    public function test_activity_log_page_filters_and_paginates(): void
    {
        $admin = $this->admin();
        foreach (range(1, 30) as $i) {
            \App\Services\ActivityLogger::log($i % 2 ? 'claim.test' : 'report.test', null, "Entry $i", [], $admin);
        }
        $this->actingAs($admin)->get('/admin/activity')->assertOk()->assertSee('Entry 30');
        $this->actingAs($admin)->get('/admin/activity?action=claim.')->assertSee('claim.test')->assertDontSee('report.test');
    }

    public function test_make_admin_command_creates_and_promotes_securely(): void
    {
        $this->artisan('ccdi:make-admin', ['email' => 'boss@example.com', '--name' => 'Boss'])
            ->expectsQuestion('Choose a password (min. 12 characters)', 'LongEnoughPass1')
            ->expectsQuestion('Repeat the password', 'LongEnoughPass1')
            ->assertSuccessful();
        $boss = User::where('email', 'boss@example.com')->firstOrFail();
        $this->assertTrue($boss->isAdmin());
        $this->assertNotSame('LongEnoughPass1', $boss->password);

        $m = $this->member(['email' => 'promote@example.com']);
        $this->artisan('ccdi:make-admin', ['email' => 'promote@example.com'])->expectsConfirmation('Promote existing account promote@example.com to administrator?', 'yes')->assertSuccessful();
        $this->assertTrue($m->fresh()->isAdmin());

        $this->artisan('ccdi:make-admin', ['email' => 'weak@example.com'])
            ->expectsQuestion('Choose a password (min. 12 characters)', 'short')
            ->expectsQuestion('Repeat the password', 'short')
            ->assertFailed();
        $this->assertDatabaseMissing('users', ['email' => 'weak@example.com']);
    }

    public function test_seeder_creates_categories_but_no_accounts(): void
    {
        $this->seed();
        $this->assertSame(7, \App\Models\Category::count());
        $this->assertSame(0, User::count());
    }
}
