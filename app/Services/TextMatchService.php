<?php

namespace App\Services;

use App\Models\ItemReport;
use App\Support\Like;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/*
 * Text-based retrieval for reports without usable photographs. Independent of Python/OpenCV:
 * it keeps working when the image matcher is not installed.
 *
 * Attributes used (thesis, Chapter 3): item name, description, color and location. Name, description and color
 * form the main word set (brand is included with them). Location is a supporting signal: sharing a location
 * word raises the score, but a location match alone never makes a suggestion (common words such as "library"
 * would otherwise flood the list).
 */
class TextMatchService
{
    private const STOP = ['the', 'and', 'with', 'for', 'was', 'that', 'this', 'from', 'have', 'has', 'near', 'left', 'lost', 'found',
        'item', 'my', 'a', 'an', 'in', 'on', 'at', 'of', 'to', 'is', 'it', 'its', 'by', 'or', 'inside', 'under', 'room'];

    /** @return Collection<int, array{report: ItemReport, score: float, shared: array<int,string>, shared_location: array<int,string>}> */
    public function suggest(ItemReport $report, int $limit = 6): Collection
    {
        $mine = $this->tokens($report);
        if (count($mine) === 0) {
            return collect();
        }

        $pool = ItemReport::query()
            ->ofType($report->report_type->opposite())
            ->published()
            ->whereKeyNot($report->id)
            ->where(function ($q) use ($mine) {
                foreach (array_slice($mine, 0, 12) as $t) {
                    foreach (['item_name', 'description', 'brand', 'color'] as $col) {
                        Like::where($q, $col, $t, 'or');
                    }
                }
            })
            ->with(['category', 'primaryImage'])
            ->limit(80)
            ->get();

        return $pool->map(function (ItemReport $cand) use ($mine, $report) {
            $theirs = $this->tokens($cand);
            $shared = array_values(array_intersect($mine, $theirs));
            $union = max(1, count(array_unique([...$mine, ...$theirs])));
            $score = count($shared) / $union;
            if ($report->category_id && $report->category_id === $cand->category_id) {
                $score += 0.15;
            }
            $sharedLocation = array_values(array_intersect($this->locationTokens($report), $this->locationTokens($cand)));
            if ($sharedLocation) {
                $score += 0.1;
            }
            if ($report->color && $cand->color && Str::lower($report->color) === Str::lower($cand->color)) {
                $score += 0.1;
            }

            return ['report' => $cand, 'score' => round($score, 3), 'shared' => $shared, 'shared_location' => $sharedLocation];
        })->filter(fn ($r) => count($r['shared']) >= 2 || ($r['score'] >= 0.3))
            ->sortByDesc('score')->take($limit)->values();
    }

    /** @return array<int,string> */
    public function locationTokens(ItemReport $r): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', Str::lower((string) $r->location_description), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(array_filter($words, fn ($w) => mb_strlen($w) >= 3 && ! in_array($w, self::STOP, true))));
    }

    /** @return array<int,string> */
    public function tokens(ItemReport $r): array
    {
        $text = Str::lower(implode(' ', array_filter([$r->item_name, $r->item_name, $r->brand, $r->color, $r->description])));
        $words = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(array_filter($words, fn ($w) => mb_strlen($w) >= 3 && ! in_array($w, self::STOP, true))));
    }
}
