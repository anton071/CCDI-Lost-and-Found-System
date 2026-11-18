<?php

namespace App\Services;

use App\Enums\MatchAlgorithm;
use App\Enums\MatchStatus;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\ImageFeature;
use App\Models\ImageMatchResult;
use App\Models\ItemReport;
use App\Models\User;
use App\Notifications\PotentialMatchFound;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Throwable;

/*
 * Orchestrates the OpenCV worker (scripts/matcher/match_images.py).
 *
 * SIFT is evaluated against every eligible opposite-type report. ORB runs only when NO candidate
 * satisfied the SIFT acceptance criterion. Results are stored as POTENTIAL matches
 * (image_match_results) with the algorithm, evidence and thresholds that produced them.
 * Nothing here ever confirms ownership, approves a claim or releases an item.
 */
class ImageMatchingService
{
    public function matchReport(ItemReport $report): MatchOutcome
    {
        if (! config('matching.enabled')) {
            return MatchOutcome::of(MatchOutcome::UNAVAILABLE, 'Image matching is switched off (MATCHER_ENABLED=false).');
        }

        $report->loadMissing('images');
        $image = $report->images->sortByDesc('is_primary')->first();
        if (! $image) {
            return MatchOutcome::of(MatchOutcome::NO_IMAGE, 'This report has no photograph, so it is searchable by its text details only.');
        }

        $candidateReports = ItemReport::query()
            ->ofType($report->report_type->opposite())
            ->published()
            ->whereKeyNot($report->id)
            ->whereHas('images')
            ->with('images')
            ->latest('id')
            ->limit(config('matching.max_candidates'))
            ->get();

        $candidates = [];
        $imageToReport = [];
        foreach ($candidateReports as $cand) {
            $img = $cand->images->sortByDesc('is_primary')->first();
            $candidates[] = ['id' => $img->id, 'path' => $this->path($img->file_path), 'sha256' => $img->sha256_hash];
            $imageToReport[$img->id] = $cand;
        }

        if (! $candidates) {
            $this->discardStalePending($report, []);

            return MatchOutcome::of(MatchOutcome::NO_CANDIDATES, 'There are no published reports with photographs of the opposite type to compare with yet.');
        }

        try {
            $output = $this->runWorker([
                'query' => ['id' => $image->id, 'path' => $this->path($image->file_path), 'sha256' => $image->sha256_hash],
                'candidates' => $candidates,
                'settings' => config('matching.settings'),
                'allowed_root' => config('matching.allowed_root'),
                'cache_dir' => config('matching.cache_dir'),
            ]);
        } catch (MatcherUnavailable $e) {
            Log::warning('Image matching unavailable', ['report' => $report->id, 'reason' => $e->getMessage()]);

            return MatchOutcome::of(MatchOutcome::UNAVAILABLE, $e->getMessage());
        }

        $stored = DB::transaction(fn () => $this->persist($report, $image->id, $imageToReport, $output));

        $stages = collect($output['stages'])->pluck('algorithm')->all();
        ActivityLogger::log('matching.run', $report, 'Image matching executed ('.implode(' → ', $stages).').', [
            'candidates' => count($candidates),
            'accepted' => $stored->count(),
            'final_algorithm' => $output['final_algorithm'],
            'fallback_used' => $output['fallback_used'],
        ], auth()->user());

        if ($stored->isEmpty()) {
            return new MatchOutcome(MatchOutcome::NO_MATCH, 'No potential match met the SIFT or ORB criteria. The report remains searchable by text.', collect(), null, (bool) $output['fallback_used']);
        }

        return new MatchOutcome(MatchOutcome::MATCHED, $stored->count().' potential match(es) found. These require review and do not prove ownership.', $stored, $output['final_algorithm'], (bool) $output['fallback_used']);
    }

    private function persist(ItemReport $report, int $queryImageId, array $imageToReport, array $output)
    {
        $this->storeFeatures($output['images'] ?? []);

        $matchedAt = now();
        $thresholds = config('matching.settings');
        $stages = [];
        foreach ($output['stages'] as $stage) {
            $best = collect($stage['evaluated'])->sortByDesc('inliers')->first();
            $stages[$stage['algorithm']] = ['seconds' => $stage['seconds'], 'candidates_evaluated' => count($stage['evaluated']), 'best_inliers' => $best['inliers'] ?? 0];
        }

        $kept = [];
        $results = new EloquentCollection();
        foreach ($output['matches'] as $m) {
            $cand = $imageToReport[$m['candidate_id']] ?? null;
            if (! $cand) {
                continue;
            }
            [$lostId, $foundId] = $report->isLost() ? [$report->id, $cand->id] : [$cand->id, $report->id];
            $existing = ImageMatchResult::where(['lost_report_id' => $lostId, 'found_report_id' => $foundId])->first();

            $row = ImageMatchResult::updateOrCreate(
                ['lost_report_id' => $lostId, 'found_report_id' => $foundId],
                [
                    'algorithm_used' => MatchAlgorithm::from($m['algorithm']),
                    'similarity_score' => $m['inliers'],   // = verified (RANSAC) correspondences, NOT a percentage
                    'good_matches' => $m['good_matches'],
                    'lost_keypoints' => $report->isLost() ? $m['query_keypoints'] : $m['candidate_keypoints'],
                    'found_keypoints' => $report->isLost() ? $m['candidate_keypoints'] : $m['query_keypoints'],
                    'matching_details' => [
                        'method' => $m['algorithm'],
                        'fallback_used' => (bool) $output['fallback_used'],
                        'inliers' => $m['inliers'],
                        'inlier_ratio' => $m['inlier_ratio'],
                        'good_matches' => $m['good_matches'],
                        'criterion' => $thresholds[strtolower($m['algorithm'])],
                        'stages' => $stages,
                        'opencv_version' => $output['opencv'] ?? null,
                        'matched_at' => $matchedAt->toIso8601String(),
                        'score_meaning' => 'Number of feature correspondences consistent with a single geometric transform (RANSAC inliers). It is evidence for review, not a similarity percentage and not proof of ownership.',
                    ],
                ]
            );

            $kept[] = $row->id;
            $results->push($row);

            if ($row->wasRecentlyCreated && ($existing === null)) {
                $this->notify($row);
            }
        }

        $this->discardStalePending($report, $kept);

        return $results->load(['lostReport', 'foundReport']);
    }

    /** Pending rows that this run did not reproduce are stale (image replaced / criteria changed): remove them. */
    private function discardStalePending(ItemReport $report, array $keepIds): void
    {
        ImageMatchResult::query()
            ->where('match_status', MatchStatus::PendingReview)
            ->where(fn ($q) => $q->where('lost_report_id', $report->id)->orWhere('found_report_id', $report->id))
            ->when($keepIds, fn ($q) => $q->whereNotIn('id', $keepIds))
            ->delete();
    }

    private function storeFeatures(array $imagesByAlgorithm): void
    {
        foreach ($imagesByAlgorithm as $algo => $images) {
            foreach ($images as $imageId => $info) {
                ImageFeature::updateOrCreate(
                    ['item_image_id' => (int) $imageId, 'algorithm' => MatchAlgorithm::from($algo)],
                    [
                        'descriptor_path' => $info['cache_file'] ?? null,
                        'keypoint_count' => $info['keypoints'] ?? null,
                        'image_width' => $info['width'] ?? null,
                        'image_height' => $info['height'] ?? null,
                        'processing_status' => empty($info['error']) ? 'completed' : 'failed',
                        'error_message' => $info['error'] ?? null,
                        'extracted_at' => empty($info['error']) ? now() : null,
                    ]
                );
            }
        }
    }

    private function notify(ImageMatchResult $row): void
    {
        $row->loadMissing(['lostReport.submitter', 'foundReport']);
        // Only tell the owner once the found report is publicly listed.
        if ($row->foundReport->report_status !== ReportStatus::Published) {
            return;
        }
        $owner = $row->lostReport->submitter;
        if ($owner instanceof User && $owner->is_active) {
            $owner->notify(new PotentialMatchFound($row));
        }
    }

    /** @throws MatcherUnavailable */
    private function runWorker(array $job): array
    {
        $script = config('matching.script');
        if (! is_file($script)) {
            throw new MatcherUnavailable('The matching worker script is missing: scripts/matcher/match_images.py.');
        }

        try {
            $result = Process::timeout(config('matching.timeout'))
                ->env(['PYTHONIOENCODING' => 'utf-8'])
                ->input(json_encode($job, JSON_THROW_ON_ERROR))
                ->run([config('matching.python'), $script]);   // argument array: never passes through a shell
        } catch (Throwable $e) {
            throw new MatcherUnavailable($this->explain($e->getMessage()));
        }

        $decoded = json_decode($result->output(), true);
        if (! is_array($decoded)) {
            // Exit 127 (Linux/macOS) and 9009 (Windows) mean the interpreter itself could not be started.
            $fallback = in_array($result->exitCode(), [127, 9009], true)
                ? 'Python was not found (exit code '.$result->exitCode().').'
                : 'The matching worker returned no usable output (exit code '.$result->exitCode().').';
            throw new MatcherUnavailable($this->explain(trim($result->errorOutput()) ?: $fallback));
        }
        if (! ($decoded['ok'] ?? false)) {
            throw new MatcherUnavailable('The matching worker reported an error: '.($decoded['error']['message'] ?? 'unknown error').' ['.($decoded['error']['code'] ?? 'engine_error').']');
        }

        return $decoded;
    }

    private function explain(string $raw): string
    {
        $raw = mb_substr($raw, 0, 400);
        if (stripos($raw, 'No module named') !== false && stripos($raw, 'cv2') !== false) {
            return 'Python is available but OpenCV is not installed. Run: pip install -r scripts/matcher/requirements.txt';
        }
        if (stripos($raw, 'not found') !== false || stripos($raw, 'not recognized') !== false || stripos($raw, 'No such file') !== false) {
            return 'Python could not be started ('.config('matching.python').'). Install Python 3 or set MATCHER_PYTHON in .env.';
        }
        if (stripos($raw, 'timed out') !== false || stripos($raw, 'timeout') !== false) {
            return 'Image matching exceeded the '.config('matching.timeout').'-second limit.';
        }

        return 'Image matching failed: '.$raw;
    }

    private function path(string $relative): string
    {
        return Storage::disk(config('ccdi.uploads.disk'))->path($relative);
    }
}
