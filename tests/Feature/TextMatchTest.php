<?php

namespace Tests\Feature;

use App\Models\ItemReport;
use App\Services\TextMatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TextMatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_text_matching_suggests_opposite_type_reports_without_any_photos_or_python(): void
    {
        config(['matching.enabled' => false, 'matching.python' => 'definitely-not-installed']);
        $lost = ItemReport::factory()->lost()->create(['item_name' => 'Blue Casio calculator', 'brand' => 'Casio', 'color' => 'blue', 'description' => 'Scientific calculator with a cracked cover']);
        $good = ItemReport::factory()->found()->create(['item_name' => 'Calculator found', 'brand' => 'Casio', 'color' => 'blue', 'description' => 'Blue scientific calculator, cover cracked']);
        ItemReport::factory()->found()->create(['item_name' => 'Red umbrella', 'color' => 'red', 'description' => 'Large umbrella']);
        ItemReport::factory()->lost()->create(['item_name' => 'Another Casio calculator', 'brand' => 'Casio']);   // same type: never suggested

        $result = app(TextMatchService::class)->suggest($lost);
        $this->assertCount(1, $result);
        $this->assertSame($good->id, $result[0]['report']->id);
        $this->assertContains('calculator', $result[0]['shared']);
    }

    public function test_shared_location_raises_the_score_but_never_creates_a_suggestion_alone(): void
    {
        $lost = ItemReport::factory()->lost()->create(['item_name' => 'Black wallet', 'color' => 'black', 'description' => 'Leather bifold wallet', 'location_description' => 'Computer Laboratory 2']);
        $sameRoom = ItemReport::factory()->found()->create(['item_name' => 'Black wallet', 'color' => 'black', 'description' => 'Leather wallet with cards', 'location_description' => 'Computer Laboratory 2']);
        $otherRoom = ItemReport::factory()->found()->create(['item_name' => 'Black wallet', 'color' => 'black', 'description' => 'Leather wallet with cards', 'location_description' => 'Canteen']);
        ItemReport::factory()->found()->create(['item_name' => 'Yellow umbrella', 'color' => 'yellow', 'description' => 'Folding umbrella', 'location_description' => 'Computer Laboratory 2']);

        $result = app(TextMatchService::class)->suggest($lost)->keyBy(fn ($r) => $r['report']->id);

        $this->assertGreaterThan($result[$otherRoom->id]['score'], $result[$sameRoom->id]['score']);
        $this->assertContains('laboratory', $result[$sameRoom->id]['shared_location']);
        $this->assertSame([], $result[$otherRoom->id]['shared_location']);
        $this->assertCount(2, $result);   // the umbrella shares only the location, so it is not suggested
    }

    public function test_unpublished_reports_are_never_suggested(): void
    {
        $lost = ItemReport::factory()->lost()->create(['item_name' => 'Silver laptop charger', 'description' => 'Silver laptop charger with long cable']);
        ItemReport::factory()->found()->pending()->create(['item_name' => 'Silver laptop charger', 'description' => 'Silver laptop charger with long cable']);
        $this->assertCount(0, app(TextMatchService::class)->suggest($lost));
    }

    public function test_owner_sees_text_suggestions_on_the_report_page_but_other_members_do_not(): void
    {
        $lost = ItemReport::factory()->lost()->create(['item_name' => 'Green notebook', 'description' => 'Green spiral notebook with physics notes']);
        ItemReport::factory()->found()->create(['item_name' => 'Notebook, green', 'description' => 'Green spiral notebook physics']);
        $this->actingAs($lost->submitter)->get("/reports/{$lost->id}")->assertSee('Possible matches from descriptions')->assertSee('Notebook, green');
        $this->actingAs($this->member())->get("/reports/{$lost->id}")->assertDontSee('Possible matches from descriptions');
    }

    public function test_tokenizer_ignores_short_and_stop_words(): void
    {
        $r = new ItemReport(['item_name' => 'The big red bag', 'description' => 'It is in a room']);
        $t = app(TextMatchService::class)->tokens($r);
        $this->assertContains('big', $t);
        $this->assertContains('red', $t);
        $this->assertNotContains('the', $t);
        $this->assertNotContains('in', $t);
    }
}
