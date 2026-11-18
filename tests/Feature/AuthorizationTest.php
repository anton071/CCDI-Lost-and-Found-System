<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Claim;
use App\Models\ItemImage;
use App\Models\ItemReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_users_cannot_reach_any_admin_route(): void
    {
        $user = $this->member();
        $report = ItemReport::factory()->create();
        $claim = Claim::factory()->create();

        foreach (['/admin', '/admin/reports', "/admin/reports/{$report->id}", '/admin/matches', '/admin/claims', "/admin/claims/{$claim->id}", '/admin/users', '/admin/activity'] as $url) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
        $this->actingAs($user)->post("/admin/claims/{$claim->id}/approve")->assertForbidden();
        $this->actingAs($user)->post("/admin/reports/{$report->id}/status", ['status' => 'archived'])->assertForbidden();
        $this->actingAs($user)->patch('/admin/users/'.$user->id, ['role' => 'admin', 'is_active' => 1])->assertForbidden();
        $this->assertSame('student', $user->fresh()->role->value);
    }

    public function test_admin_can_open_admin_pages(): void
    {
        $admin = $this->admin();
        foreach (['/admin', '/admin/reports', '/admin/matches', '/admin/claims', '/admin/users', '/admin/activity'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_unpublished_reports_are_hidden_from_other_users_and_guests(): void
    {
        $owner = $this->member();
        $pending = ItemReport::factory()->found()->pending()->create(['submitted_by' => $owner->id]);

        $this->get("/reports/{$pending->id}")->assertNotFound();
        $this->actingAs($this->member())->get("/reports/{$pending->id}")->assertNotFound();
        $this->actingAs($owner)->get("/reports/{$pending->id}")->assertOk();
        $this->actingAs($this->admin())->get("/reports/{$pending->id}")->assertOk();
    }

    public function test_identifying_details_are_never_shown_to_other_members(): void
    {
        $finder = $this->member();
        $report = ItemReport::factory()->found()->create(['submitted_by' => $finder->id, 'identifying_features' => 'SECRET-SERIAL-12345']);

        $this->get("/reports/{$report->id}")->assertOk()->assertDontSee('SECRET-SERIAL-12345');
        $this->actingAs($this->member())->get("/reports/{$report->id}")->assertOk()->assertDontSee('SECRET-SERIAL-12345');
        $this->actingAs($finder)->get("/reports/{$report->id}")->assertSee('SECRET-SERIAL-12345');
        $this->actingAs($this->admin())->get("/reports/{$report->id}")->assertSee('SECRET-SERIAL-12345');
    }

    public function test_identifying_details_are_not_searchable(): void
    {
        ItemReport::factory()->found()->create(['item_name' => 'Plain bag', 'identifying_features' => 'ZXQ-hidden-marker']);
        $this->get('/items?q=ZXQ-hidden-marker')->assertOk()->assertDontSee('Plain bag');
    }

    public function test_found_item_photos_require_sign_in_but_lost_item_photos_do_not(): void
    {
        Storage::fake('local');
        $found = ItemReport::factory()->found()->create();
        $lost = ItemReport::factory()->lost()->create();
        foreach ([$found, $lost] as $r) {
            Storage::disk('local')->put("item-images/{$r->id}.jpg", 'x');
            ItemImage::create(['item_report_id' => $r->id, 'file_path' => "item-images/{$r->id}.jpg", 'mime_type' => 'image/jpeg', 'is_primary' => true]);
        }
        $foundImg = $found->images()->first();
        $lostImg = $lost->images()->first();

        $this->get("/images/{$foundImg->id}")->assertNotFound();
        $this->actingAs($this->member())->get("/images/{$foundImg->id}")->assertOk();
        $this->get("/images/{$lostImg->id}")->assertOk();
    }

    public function test_images_of_unpublished_reports_are_private_to_owner_and_admin(): void
    {
        Storage::fake('local');
        $owner = $this->member();
        $r = ItemReport::factory()->lost()->pending()->create(['submitted_by' => $owner->id]);
        Storage::disk('local')->put('item-images/p.jpg', 'x');
        $img = ItemImage::create(['item_report_id' => $r->id, 'file_path' => 'item-images/p.jpg', 'mime_type' => 'image/jpeg', 'is_primary' => true]);

        $this->get("/images/{$img->id}")->assertNotFound();
        $this->actingAs($this->member())->get("/images/{$img->id}")->assertNotFound();
        $this->actingAs($owner)->get("/images/{$img->id}")->assertOk();
        $this->actingAs($this->admin())->get("/images/{$img->id}")->assertOk();
    }

    public function test_members_cannot_view_other_members_claims(): void
    {
        $claim = Claim::factory()->create();
        $this->actingAs($this->member())->get("/claims/{$claim->id}")->assertForbidden();
        $this->actingAs($claim->claimant)->get("/claims/{$claim->id}")->assertOk();
        $this->actingAs($this->member())->post("/claims/{$claim->id}/cancel")->assertForbidden();
    }

    public function test_member_cannot_see_potential_matches_of_someone_elses_lost_report(): void
    {
        $lost = ItemReport::factory()->lost()->create();
        $this->actingAs($this->member())->get("/reports/{$lost->id}")->assertOk()->assertDontSee('Possible matches from photographs');
        $this->actingAs($lost->submitter)->get("/reports/{$lost->id}")->assertSee('Possible matches from photographs');
    }
}
