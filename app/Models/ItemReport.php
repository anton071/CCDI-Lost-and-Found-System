<?php

namespace App\Models;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Support\Like;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/*
 * Workflow columns (report_status, reviewed_*, reference_code, submitted_by) are not
 * mass-assignable: they are set only by the services that own those transitions.
 */
#[Fillable([
    'category_id', 'item_name', 'description', 'brand', 'color',
    'identifying_features', 'location_description', 'incident_date', 'incident_time',
])]
class ItemReport extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'report_type' => ReportType::class,
            'report_status' => ReportStatus::class,
            'incident_date' => 'date',
            'reviewed_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ItemImage::class);
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ItemImage::class)->orderByDesc('is_primary')->orderBy('id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class, 'found_report_id');
    }

    /** Matches in which this report is the lost side. */
    public function matchesAsLost(): HasMany
    {
        return $this->hasMany(ImageMatchResult::class, 'lost_report_id');
    }

    /** Matches in which this report is the found side. */
    public function matchesAsFound(): HasMany
    {
        return $this->hasMany(ImageMatchResult::class, 'found_report_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('report_status', ReportStatus::Published);
    }

    public function scopeOfType(Builder $query, ReportType $type): Builder
    {
        return $query->where('report_type', $type);
    }

    /**
     * Database-backed text search + filters. Every search term must appear in at least one
     * searchable field (identifying_features is intentionally NOT searchable: it is verification data).
     *
     * @param  array{q?:?string,type?:?string,category?:?int,location?:?string,from?:?string,to?:?string,status?:?string}  $f
     */
    public function scopeFilter(Builder $query, array $f): Builder
    {
        if (filled($f['q'] ?? null)) {
            $terms = preg_split('/\s+/u', trim(mb_strtolower($f['q'])), -1, PREG_SPLIT_NO_EMPTY);
            foreach (array_slice($terms, 0, 6) as $term) {
                $query->where(function (Builder $w) use ($term) {
                    foreach (['item_name', 'description', 'brand', 'color', 'location_description'] as $col) {
                        Like::where($w, $col, $term, 'or');
                    }
                });
            }
        }
        $query->when(filled($f['type'] ?? null), fn ($q) => $q->where('report_type', $f['type']))
            ->when(filled($f['category'] ?? null), fn ($q) => $q->where('category_id', $f['category']))
            ->when(filled($f['location'] ?? null), fn ($q) => Like::where($q, 'location_description', $f['location']))
            ->when(filled($f['from'] ?? null), fn ($q) => $q->whereDate('incident_date', '>=', $f['from']))
            ->when(filled($f['to'] ?? null), fn ($q) => $q->whereDate('incident_date', '<=', $f['to']))
            ->when(filled($f['status'] ?? null), fn ($q) => $q->where('report_status', $f['status']));

        return $query;
    }

    public function isLost(): bool
    {
        return $this->report_type === ReportType::Lost;
    }

    public function isFound(): bool
    {
        return $this->report_type === ReportType::Found;
    }
}
