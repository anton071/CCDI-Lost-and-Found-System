<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\ItemImage;
use App\Models\ItemReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $over = []): array
    {
        return array_merge([
            'category_id' => $this->category()->id,
            'item_name' => 'Black umbrella',
            'description' => 'Large black umbrella with a wooden handle.',
            'location_description' => 'Library, second floor',
            'incident_date' => now()->subDay()->toDateString(),
        ], $over);
    }

    public function test_member_can_report_a_lost_item_which_is_listed_immediately(): void
    {
        $user = $this->member();
        $res = $this->actingAs($user)->post('/reports/new/lost', $this->data(['brand' => 'Fulton', 'color' => 'black']));
        $report = ItemReport::firstOrFail();
        $res->assertRedirect("/reports/{$report->id}");
        $this->assertSame(ReportType::Lost, $report->report_type);
        $this->assertSame(ReportStatus::Published, $report->report_status);
        $this->assertSame($user->id, $report->submitted_by);
        $this->assertMatchesRegularExpression('/^L-\d{6}-[A-Z0-9]{5}$/', $report->reference_code);
        $this->assertDatabaseHas('activity_logs', ['action' => 'report.created', 'subject_id' => $report->id]);
    }

    public function test_found_reports_wait_for_administrator_review(): void
    {
        $this->actingAs($this->member())->post('/reports/new/found', $this->data())->assertSessionHasNoErrors();
        $report = ItemReport::firstOrFail();
        $this->assertSame(ReportType::Found, $report->report_type);
        $this->assertSame(ReportStatus::PendingReview, $report->report_status);
        $this->get('/items')->assertDontSee('Black umbrella');
    }

    public function test_lost_reports_can_also_require_review_by_configuration(): void
    {
        config(['ccdi.auto_publish_lost' => false]);
        $this->actingAs($this->member())->post('/reports/new/lost', $this->data());
        $this->assertSame(ReportStatus::PendingReview, ItemReport::firstOrFail()->report_status);
    }

    public function test_workflow_fields_cannot_be_mass_assigned(): void
    {
        $other = $this->member();
        $user = $this->member();
        $this->actingAs($user)->post('/reports/new/found', $this->data([
            'report_status' => 'published', 'submitted_by' => $other->id, 'report_type' => 'lost', 'reviewed_by' => $other->id,
        ]));
        $r = ItemReport::firstOrFail();
        $this->assertSame(ReportStatus::PendingReview, $r->report_status);
        $this->assertSame($user->id, $r->submitted_by);
        $this->assertSame(ReportType::Found, $r->report_type);
        $this->assertNull($r->reviewed_by);
    }

    public function test_required_and_optional_fields_are_validated(): void
    {
        $u = $this->member();
        $this->actingAs($u)->post('/reports/new/lost', [])->assertSessionHasErrors(['category_id', 'item_name', 'description', 'location_description', 'incident_date']);
        $this->actingAs($u)->post('/reports/new/lost', $this->data(['incident_date' => now()->addDay()->toDateString()]))->assertSessionHasErrors('incident_date');
        $this->actingAs($u)->post('/reports/new/lost', $this->data(['category_id' => 9999]))->assertSessionHasErrors('category_id');
        $this->actingAs($u)->post('/reports/new/lost', $this->data(['incident_time' => 'noonish']))->assertSessionHasErrors('incident_time');
        // brand, colour, time, private details and photo are optional
        $this->actingAs($u)->post('/reports/new/lost', $this->data())->assertSessionHasNoErrors();
        $this->assertSame(1, ItemReport::count());
    }

    public function test_inactive_categories_are_rejected(): void
    {
        $cat = $this->category();
        $cat->update(['is_active' => false]);
        $this->actingAs($this->member())->post('/reports/new/lost', $this->data(['category_id' => $cat->id]))->assertSessionHasErrors('category_id');
    }

    public function test_unknown_report_type_is_not_found(): void
    {
        $this->actingAs($this->member())->get('/reports/new/stolen')->assertNotFound();
    }

    public function test_valid_photo_is_stored_privately_under_a_random_name(): void
    {
        Storage::fake('local');
        $this->actingAs($this->member())->post('/reports/new/lost', $this->data(['photo' => $this->jpeg('my vacation <script>.jpg')]))->assertSessionHasNoErrors();
        $img = ItemImage::firstOrFail();
        Storage::disk('local')->assertExists($img->file_path);
        $this->assertStringStartsWith('item-images/', $img->file_path);
        $this->assertMatchesRegularExpression('#^item-images/\d{4}/\d{2}/[0-9a-f-]{36}\.jpg$#', $img->file_path);
        $this->assertStringNotContainsString('vacation', $img->file_path);
        $this->assertSame('image/jpeg', $img->mime_type);
        $this->assertSame(64, strlen($img->sha256_hash));
        $this->assertTrue($img->is_primary);
        $this->assertSame(hash('sha256', Storage::disk('local')->get($img->file_path)), $img->sha256_hash);
    }

    public function test_non_image_and_disguised_files_are_rejected(): void
    {
        Storage::fake('local');
        $u = $this->member();
        $php = UploadedFile::fake()->createWithContent('shell.jpg', '<?php system($_GET["c"]); ?>');
        $txt = UploadedFile::fake()->createWithContent('notes.png', 'just some text');
        $gif = UploadedFile::fake()->image('anim.gif');
        $pdf = UploadedFile::fake()->create('doc.pdf', 20, 'application/pdf');
        foreach ([$php, $txt, $gif, $pdf] as $bad) {
            $this->actingAs($u)->post('/reports/new/lost', $this->data(['photo' => $bad]))->assertSessionHasErrors('photo');
        }
        $this->assertSame(0, ItemReport::count());
        $this->assertSame(0, ItemImage::count());
    }

    public function test_oversized_and_tiny_photos_are_rejected(): void
    {
        $u = $this->member();
        $this->actingAs($u)->post('/reports/new/lost', $this->data(['photo' => UploadedFile::fake()->image('big.jpg', 800, 800)->size(31000)]))->assertSessionHasErrors('photo');
        $this->actingAs($u)->post('/reports/new/lost', $this->data(['photo' => UploadedFile::fake()->image('tiny.jpg', 20, 20)]))->assertSessionHasErrors('photo');
        $this->assertSame(0, ItemReport::count());
    }

    public function test_payload_appended_to_a_real_jpeg_is_stripped_by_reencoding(): void
    {
        Storage::fake('local');
        $src = $this->jpeg();
        $path = tempnam(sys_get_temp_dir(), 'poly');
        file_put_contents($path, file_get_contents($src->getRealPath())."\n<?php system('id'); ?>");
        $poly = new UploadedFile($path, 'poly.jpg', 'image/jpeg', null, true);

        $this->actingAs($this->member())->post('/reports/new/lost', $this->data(['photo' => $poly]))->assertSessionHasNoErrors();
        $stored = Storage::disk('local')->get(ItemImage::firstOrFail()->file_path);
        $this->assertStringNotContainsString('<?php', $stored);
        $this->assertNotFalse(@imagecreatefromstring($stored));
    }

    public function test_report_without_photo_is_saved_and_viewable(): void
    {
        $u = $this->member();
        $this->actingAs($u)->post('/reports/new/lost', $this->data());
        $r = ItemReport::firstOrFail();
        $this->assertCount(0, $r->images);
        $this->actingAs($u)->get("/reports/{$r->id}")->assertOk()->assertSee('No photo');
    }

    public function test_forms_and_listings_render(): void
    {
        $u = $this->member();
        $this->category();
        $this->actingAs($u)->get('/reports/new/lost')->assertOk()->assertSee('Report a lost item');
        $this->actingAs($u)->get('/reports/new/found')->assertOk()->assertSee('Report a found item');
        $this->actingAs($u)->get('/my/reports')->assertOk()->assertSee('You have not submitted any reports');
        $this->actingAs($u)->get('/dashboard')->assertOk();
    }

    public function test_members_only_see_their_own_reports_in_my_reports(): void
    {
        $a = $this->member();
        $b = $this->member();
        ItemReport::factory()->create(['submitted_by' => $a->id, 'item_name' => 'Alpha thing']);
        ItemReport::factory()->create(['submitted_by' => $b->id, 'item_name' => 'Beta thing']);
        $this->actingAs($a)->get('/my/reports')->assertSee('Alpha thing')->assertDontSee('Beta thing');
    }
}
