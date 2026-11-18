<?php

namespace Database\Factories;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\Category;
use App\Models\ItemReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ItemReport> */
class ItemReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reference_code' => strtoupper(Str::random(1)).'-'.now()->format('ymd').'-'.strtoupper(Str::random(5)),
            'submitted_by' => User::factory(),
            'category_id' => Category::factory(),
            'report_type' => ReportType::Lost,
            'item_name' => fake()->words(3, true),
            'description' => fake()->sentence(12),
            'brand' => null,
            'color' => fake()->safeColorName(),
            'identifying_features' => 'Scratch on the left side and a blue sticker.',
            'location_description' => 'Library, second floor',
            'incident_date' => now()->subDays(2)->toDateString(),
            'report_status' => ReportStatus::Published,
        ];
    }

    public function lost(): static
    {
        return $this->state(fn () => ['report_type' => ReportType::Lost]);
    }

    public function found(): static
    {
        return $this->state(fn () => ['report_type' => ReportType::Found]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['report_status' => ReportStatus::PendingReview]);
    }

    public function status(ReportStatus $status): static
    {
        return $this->state(fn () => ['report_status' => $status]);
    }
}
