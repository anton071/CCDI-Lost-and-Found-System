<?php

namespace Tests\Feature;

use App\Enums\MatchStatus;
use App\Enums\ReportStatus;
use App\Models\Claim;
use App\Models\ImageFeature;
use App\Models\ImageMatchResult;
use App\Models\ItemReport;
use App\Models\User;
use App\Notifications\PotentialMatchFound;
use App\Services\ImageMatchingService;
use App\Services\ImageStorage;
use App\Services\MatchOutcome;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/*
 * These tests drive the REAL OpenCV worker (SIFT, ORB). They are skipped - not faked - when Python,
 * OpenCV or scikit-image (used only to generate the sample photographs) are unavailable.
 */
class ImageMatchingTest extends TestCase
{
    use RefreshDatabase;

    private static string $dataset = '';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$dataset = sys_get_temp_dir().'/ccdi-sample-'.getmypid();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$dataset && is_dir(self::$dataset)) {
            array_map('unlink', glob(self::$dataset.'/*'));
            @rmdir(self::$dataset);
        }
        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $python = env('MATCHER_PYTHON', 'python');
        $probe = Process::run([$python, '-c', 'import cv2, skimage; assert hasattr(cv2, "SIFT_create")']);
        if ($probe->failed()) {
            $this->markTestSkipped('Image-matching dependencies unavailable (python/opencv/scikit-image): '.trim(substr($probe->errorOutput(), -120)));
        }
        if (! is_dir(self::$dataset)) {
            $gen = Process::run([$python, base_path('scripts/matcher/make_sample_dataset.py'), self::$dataset]);
            if ($gen->failed()) {
                $this->markTestSkipped('Could not generate the sample dataset: '.$gen->errorOutput());
            }
        }

        Storage::fake('local');
        config([
            'matching.enabled' => true,
            'matching.python' => $python,
            'matching.allowed_root' => Storage::disk('local')->path(''),
            'matching.cache_dir' => Storage::disk('local')->path('features'),
        ]);
    }

    private function photo(string $name): UploadedFile
    {
        return new UploadedFile(self::$dataset.'/'.$name, $name, 'image/jpeg', null, true);
    }

    private function make(string $type, string $file, array $over = []): ItemReport
    {
        $user = User::factory()->create();
        $report = ItemReport::factory()->{$type}()->create(array_merge(['submitted_by' => $user->id], $over));
        app(ImageStorage::class)->store($this->photo($file), $report, $user);

        return $report->fresh();
    }

    public function test_sift_finds_the_same_object_photographed_differently_and_stores_evidence(): void
    {
        Notification::fake();
        $found = $this->make('found', 'found_astronaut.jpg');
        $this->make('found', 'found_coffee.jpg');
        $lost = $this->make('lost', 'lost_astronaut_rotated.jpg');

        $outcome = app(ImageMatchingService::class)->matchReport($lost);

        $this->assertSame(MatchOutcome::MATCHED, $outcome->status);
        $this->assertSame('SIFT', $outcome->algorithm);
        $this->assertFalse($outcome->fallbackUsed);
        $this->assertCount(1, $outcome->matches);
        $row = ImageMatchResult::firstOrFail();
        $this->assertSame($lost->id, $row->lost_report_id);
        $this->assertSame($found->id, $row->found_report_id);
        $this->assertSame('SIFT', $row->algorithm_used->value);
        $this->assertSame(MatchStatus::PendingReview, $row->match_status);
        $this->assertGreaterThanOrEqual(config('matching.settings.sift.min_inliers'), (int) $row->similarity_score);
        $this->assertGreaterThan(0, $row->good_matches);
        $d = $row->matching_details;
        $this->assertSame('SIFT', $d['method']);
        $this->assertArrayHasKey('criterion', $d);
        $this->assertArrayHasKey('matched_at', $d);
        $this->assertStringContainsString('not a similarity percentage', $d['score_meaning']);
        $this->assertGreaterThan(0, ImageFeature::where('algorithm', 'SIFT')->where('processing_status', 'completed')->count());
        $this->assertSame(ReportStatus::Published, $found->fresh()->report_status, 'a match must never change report status');
        $this->assertSame(0, Claim::count(), 'a match must never create or approve a claim');
        Notification::assertSentTo($lost->submitter, PotentialMatchFound::class);
        $this->assertDatabaseHas('activity_logs', ['action' => 'matching.run', 'subject_id' => $lost->id]);
    }

    #[DataProvider('variants')]
    public function test_sift_handles_viewpoint_lighting_blur_and_crop_variations(string $lostFile, string $foundFile): void
    {
        $this->make('found', $foundFile);
        $this->make('found', 'found_chelsea.jpg');
        $lost = $this->make('lost', $lostFile);
        $outcome = app(ImageMatchingService::class)->matchReport($lost);
        $this->assertSame(MatchOutcome::MATCHED, $outcome->status, $outcome->message);
        $this->assertSame($outcome->matches->first()->foundReport->images->first()->original_filename, $foundFile);
    }

    public static function variants(): array
    {
        return [
            'rotated + scaled' => ['lost_astronaut_rotated.jpg', 'found_astronaut.jpg'],
            'dim + blurred + low JPEG quality' => ['lost_astronaut_dim_blur.jpg', 'found_astronaut.jpg'],
            'perspective change' => ['lost_astronaut_perspective.jpg', 'found_astronaut.jpg'],
            'cropped' => ['lost_coffee_crop.jpg', 'found_coffee.jpg'],
            'rotated second object' => ['lost_rocket_shifted.jpg', 'found_rocket.jpg'],
        ];
    }

    public function test_unrelated_objects_produce_a_clear_no_match_after_orb_fallback_also_fails(): void
    {
        foreach (['found_astronaut.jpg', 'found_coffee.jpg', 'found_chelsea.jpg', 'found_rocket.jpg'] as $f) {
            $this->make('found', $f);
        }
        foreach (['lost_negative_camera.jpg', 'lost_negative_coins.jpg'] as $neg) {
            $lost = $this->make('lost', $neg);
            $outcome = app(ImageMatchingService::class)->matchReport($lost);
            $this->assertSame(MatchOutcome::NO_MATCH, $outcome->status, $neg);
            $this->assertTrue($outcome->fallbackUsed, 'ORB must have been tried because SIFT found nothing acceptable');
            $this->assertCount(0, $outcome->matches);
        }
        $this->assertSame(0, ImageMatchResult::count());
    }

    public function test_orb_runs_only_when_sift_criteria_are_not_met(): void
    {
        $this->make('found', 'found_astronaut.jpg');
        $lost = $this->make('lost', 'lost_astronaut_rotated.jpg');

        // SIFT satisfied -> ORB must NOT run
        $res = $this->runWorkerDirect($lost);
        $this->assertFalse($res['fallback_used']);
        $this->assertSame(['SIFT'], array_column($res['stages'], 'algorithm'));

        // SIFT criterion impossible -> ORB runs as the fallback and may produce the match
        config(['matching.settings.sift.min_inliers' => 100000]);
        $outcome = app(ImageMatchingService::class)->matchReport($lost);
        $this->assertTrue($outcome->fallbackUsed);
        $this->assertSame(MatchOutcome::MATCHED, $outcome->status);
        $this->assertSame('ORB', $outcome->algorithm);
        $row = ImageMatchResult::firstOrFail();
        $this->assertSame('ORB', $row->algorithm_used->value);
        $this->assertTrue($row->matching_details['fallback_used']);
        $this->assertArrayHasKey('SIFT', $row->matching_details['stages']);
        $this->assertArrayHasKey('ORB', $row->matching_details['stages']);
    }

    public function test_featureless_and_corrupt_images_do_not_crash_matching(): void
    {
        $this->make('found', 'found_astronaut.jpg');
        $this->make('found', 'found_coffee.jpg');
        foreach (['lost_blank.jpg', 'lost_gradient.jpg'] as $f) {
            $lost = $this->make('lost', $f);
            $outcome = app(ImageMatchingService::class)->matchReport($lost);
            $this->assertSame(MatchOutcome::NO_MATCH, $outcome->status, $f);
        }
        // A corrupt candidate on disk is skipped, the rest still match
        $found = ItemReport::factory()->found()->create();
        Storage::disk('local')->put('item-images/corrupt.jpg', 'not an image at all');
        \App\Models\ItemImage::create(['item_report_id' => $found->id, 'file_path' => 'item-images/corrupt.jpg', 'mime_type' => 'image/jpeg', 'is_primary' => true]);
        $lost = $this->make('lost', 'lost_astronaut_rotated.jpg');
        $outcome = app(ImageMatchingService::class)->matchReport($lost);
        $this->assertSame(MatchOutcome::MATCHED, $outcome->status);
        $this->assertSame(1, $outcome->matches->count());
    }

    public function test_reports_without_photos_are_not_image_matched(): void
    {
        $lost = ItemReport::factory()->lost()->create();
        $outcome = app(ImageMatchingService::class)->matchReport($lost);
        $this->assertSame(MatchOutcome::NO_IMAGE, $outcome->status);
        $this->assertSame(0, ImageMatchResult::count());
    }

    public function test_same_type_reports_are_never_compared(): void
    {
        $this->make('lost', 'found_astronaut.jpg');
        $second = $this->make('lost', 'found_astronaut.jpg');
        $outcome = app(ImageMatchingService::class)->matchReport($second);
        $this->assertSame(MatchOutcome::NO_CANDIDATES, $outcome->status);
        $this->assertSame(0, ImageMatchResult::count());
    }

    public function test_unpublished_found_reports_are_not_candidates(): void
    {
        $f = $this->make('found', 'found_astronaut.jpg');
        $f->forceFill(['report_status' => ReportStatus::PendingReview])->save();
        $lost = $this->make('lost', 'lost_astronaut_rotated.jpg');
        $this->assertSame(MatchOutcome::NO_CANDIDATES, app(ImageMatchingService::class)->matchReport($lost)->status);
    }

    public function test_missing_python_is_reported_honestly_and_never_simulates_success(): void
    {
        config(['matching.python' => 'definitely-not-installed-python']);
        $this->make('found', 'found_astronaut.jpg');
        $lost = $this->make('lost', 'lost_astronaut_rotated.jpg');
        $outcome = app(ImageMatchingService::class)->matchReport($lost);
        $this->assertSame(MatchOutcome::UNAVAILABLE, $outcome->status);
        $this->assertTrue($outcome->failed());
        $this->assertStringContainsString('Python', $outcome->message);
        $this->assertSame(0, ImageMatchResult::count());
    }

    public function test_worker_timeout_and_garbage_output_are_handled(): void
    {
        $this->make('found', 'found_astronaut.jpg');
        $lost = $this->make('lost', 'lost_astronaut_rotated.jpg');

        Process::fake(['*' => Process::result(output: 'this is not json', errorOutput: 'Traceback ... ModuleNotFoundError: No module named \'cv2\'', exitCode: 1)]);
        $o = app(ImageMatchingService::class)->matchReport($lost);
        $this->assertSame(MatchOutcome::UNAVAILABLE, $o->status);
        $this->assertStringContainsString('OpenCV is not installed', $o->message);

        Process::fake(['*' => Process::result(output: json_encode(['ok' => false, 'error' => ['code' => 'file_missing', 'message' => 'Image file does not exist.']]), exitCode: 2)]);
        $o = app(ImageMatchingService::class)->matchReport($lost);
        $this->assertSame(MatchOutcome::UNAVAILABLE, $o->status);
        $this->assertStringContainsString('file_missing', $o->message);
        $this->assertSame(0, ImageMatchResult::count());
    }

    public function test_report_submission_still_succeeds_when_the_matcher_is_broken(): void
    {
        config(['matching.python' => 'definitely-not-installed-python']);
        $this->make('found', 'found_astronaut.jpg');
        $user = User::factory()->create();
        $cat = \App\Models\Category::factory()->create();

        $res = $this->actingAs($user)->post('/reports/new/lost', [
            'category_id' => $cat->id, 'item_name' => 'Lost astronaut photo', 'description' => 'A printed photograph of an astronaut',
            'location_description' => 'Library', 'incident_date' => now()->toDateString(),
            'photo' => $this->photo('lost_astronaut_rotated.jpg'),
        ]);
        $report = ItemReport::where('item_name', 'Lost astronaut photo')->firstOrFail();
        $res->assertRedirect("/reports/{$report->id}")->assertSessionHas('match_outcome.status', 'unavailable');
        $this->assertCount(1, $report->images);
        $this->actingAs($user)->get("/reports/{$report->id}")->assertOk();   // text retrieval remains available
    }

    public function test_reviewed_matches_survive_a_rerun_and_stale_pending_rows_are_removed(): void
    {
        $this->make('found', 'found_astronaut.jpg');
        $lost = $this->make('lost', 'lost_astronaut_rotated.jpg');
        $svc = app(ImageMatchingService::class);
        $svc->matchReport($lost);
        $row = ImageMatchResult::firstOrFail();
        $row->forceFill(['match_status' => MatchStatus::Dismissed])->save();

        $svc->matchReport($lost);   // rerun keeps the human decision
        $this->assertSame(1, ImageMatchResult::count());
        $this->assertSame(MatchStatus::Dismissed, $row->fresh()->match_status);

        $row->forceFill(['match_status' => MatchStatus::PendingReview])->save();
        config(['matching.settings.sift.min_inliers' => 100000, 'matching.settings.orb.min_inliers' => 100000]);
        $svc->matchReport($lost);   // criteria no longer satisfied -> stale pending row is invalidated
        $this->assertSame(0, ImageMatchResult::count());
    }

    public function test_descriptors_are_cached_and_reused(): void
    {
        $this->make('found', 'found_astronaut.jpg');
        $lost = $this->make('lost', 'lost_astronaut_rotated.jpg');
        app(ImageMatchingService::class)->matchReport($lost);
        $cached = glob(config('matching.cache_dir').'/*_sift_*.npz');
        $this->assertCount(2, $cached);
        $before = array_map('filemtime', $cached);
        app(ImageMatchingService::class)->matchReport($lost);
        $this->assertSame($before, array_map('filemtime', $cached), 'cached descriptor files must not be rewritten');
    }

    public function test_publishing_a_found_report_triggers_matching_against_published_lost_reports(): void
    {
        Notification::fake();
        $lost = $this->make('lost', 'lost_astronaut_rotated.jpg');
        $found = $this->make('found', 'found_astronaut.jpg');
        $found->forceFill(['report_status' => ReportStatus::PendingReview])->save();
        $this->assertSame(0, ImageMatchResult::count());

        $this->actingAs($this->admin())->post("/admin/reports/{$found->id}/status", ['status' => 'published'])->assertSessionHasNoErrors();
        $this->assertSame(1, ImageMatchResult::count());
        Notification::assertSentTo($lost->submitter, PotentialMatchFound::class);
    }

    public function test_owner_sees_the_match_with_honest_wording_and_other_members_do_not(): void
    {
        $this->make('found', 'found_astronaut.jpg');
        $lost = $this->make('lost', 'lost_astronaut_rotated.jpg');
        app(ImageMatchingService::class)->matchReport($lost);

        $page = $this->actingAs($lost->submitter)->get("/reports/{$lost->id}")->assertOk();
        $page->assertSee('verified feature correspondences')->assertSee('not a percentage', false)->assertSee('not proof of ownership', false);
        $page->assertDontSee('Confirmed')->assertDontSee('Owner verified');
        $this->actingAs($this->member())->get("/reports/{$lost->id}")->assertDontSee('verified feature correspondences');
    }

    private function runWorkerDirect(ItemReport $report): array
    {
        $svc = app(ImageMatchingService::class);
        $m = new \ReflectionMethod($svc, 'runWorker');
        $m->setAccessible(true);
        $img = $report->images->first();
        $cands = ItemReport::ofType($report->report_type->opposite())->published()->whereHas('images')->with('images')->get()
            ->map(fn ($c) => ['id' => $c->images->first()->id, 'path' => Storage::disk('local')->path($c->images->first()->file_path), 'sha256' => $c->images->first()->sha256_hash])->all();

        return $m->invoke($svc, [
            'query' => ['id' => $img->id, 'path' => Storage::disk('local')->path($img->file_path), 'sha256' => $img->sha256_hash],
            'candidates' => $cands, 'settings' => config('matching.settings'),
            'allowed_root' => config('matching.allowed_root'), 'cache_dir' => config('matching.cache_dir'),
        ]);
    }
}
