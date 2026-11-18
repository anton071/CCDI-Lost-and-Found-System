<?php

namespace Database\Factories;

use App\Enums\ClaimStatus;
use App\Enums\ReportType;
use App\Models\Claim;
use App\Models\ItemReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Claim> */
class ClaimFactory extends Factory
{
    public function definition(): array
    {
        return [
            'found_report_id' => ItemReport::factory()->found(),
            'claimant_user_id' => User::factory(),
            'claim_message' => 'It has a blue sticker and a scratch on the left side; I lost it on Monday.',
            'status' => ClaimStatus::Pending,
        ];
    }

    public function status(ClaimStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
