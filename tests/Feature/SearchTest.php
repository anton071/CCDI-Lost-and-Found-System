<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Category;
use App\Models\ItemReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_text_search_matches_name_description_brand_colour_and_location(): void
    {
        ItemReport::factory()->found()->create(['item_name' => 'Calculator', 'brand' => 'Casio', 'color' => 'grey', 'description' => 'Scientific model', 'location_description' => 'Room 204']);
        ItemReport::factory()->found()->create(['item_name' => 'Umbrella', 'brand' => 'Fulton', 'color' => 'red']);

        foreach (['calculator', 'casio', 'grey', 'scientific', '204'] as $q) {
            $this->get('/items?q='.$q)->assertOk()->assertSee('Calculator')->assertDontSee('Umbrella');
        }
        $this->get('/items?q=casio+grey')->assertSee('Calculator');
        $this->get('/items?q=casio+red')->assertDontSee('Calculator')->assertDontSee('Umbrella');
    }

    public function test_reports_without_photographs_are_searchable(): void
    {
        ItemReport::factory()->lost()->create(['item_name' => 'Textless wallet']);
        $this->get('/items?q=wallet')->assertOk()->assertSee('Textless wallet')->assertSee('No photo');
    }

    public function test_filters_by_type_category_location_and_dates(): void
    {
        $keys = Category::factory()->create(['name' => 'Keys cat']);
        $bags = Category::factory()->create(['name' => 'Bags cat']);
        ItemReport::factory()->lost()->create(['item_name' => 'Lost keys', 'category_id' => $keys->id, 'location_description' => 'Cafeteria', 'incident_date' => '2026-03-10']);
        ItemReport::factory()->found()->create(['item_name' => 'Found bag', 'category_id' => $bags->id, 'location_description' => 'Gym', 'incident_date' => '2026-04-20']);

        $this->get('/items?type=lost')->assertSee('Lost keys')->assertDontSee('Found bag');
        $this->get('/items?type=found')->assertSee('Found bag')->assertDontSee('Lost keys');
        $this->get("/items?category={$bags->id}")->assertSee('Found bag')->assertDontSee('Lost keys');
        $this->get('/items?location=caf')->assertSee('Lost keys')->assertDontSee('Found bag');
        $this->get('/items?from=2026-04-01&to=2026-04-30')->assertSee('Found bag')->assertDontSee('Lost keys');
        $this->get('/items?from=2026-03-01&to=2026-03-31')->assertSee('Lost keys')->assertDontSee('Found bag');
    }

    public function test_invalid_filters_are_rejected_server_side(): void
    {
        $this->get('/items?type=stolen')->assertSessionHasErrors('type');
        $this->get('/items?category=abc')->assertSessionHasErrors('category');
        $this->get('/items?from=2026-05-01&to=2026-04-01')->assertSessionHasErrors('to');
        $this->get('/items?q='.str_repeat('a', 101))->assertSessionHasErrors('q');
    }

    public function test_only_published_reports_are_listed(): void
    {
        foreach (ReportStatus::cases() as $s) {
            ItemReport::factory()->create(['item_name' => 'Item '.$s->value, 'report_status' => $s]);
        }
        $page = $this->get('/items')->assertOk();
        $page->assertSee('Item published');
        foreach (['pending_review', 'rejected', 'resolved', 'archived'] as $hidden) {
            $page->assertDontSee('Item '.$hidden);
        }
    }

    public function test_results_are_paginated(): void
    {
        ItemReport::factory()->count(30)->create();
        $this->get('/items')->assertOk()->assertSee('30 results');
        $this->get('/items?page=3')->assertOk();
        $this->assertCount(12, $this->get('/items')->viewData('items'));
        $this->assertCount(6, $this->get('/items?page=3')->viewData('items'));
    }

    public function test_empty_state_is_shown_when_nothing_matches(): void
    {
        $this->get('/items?q=nothing-like-this')->assertOk()->assertSee('No items match your search')->assertSee('0 results');
    }

    public function test_like_wildcards_in_the_query_are_treated_literally(): void
    {
        ItemReport::factory()->create(['item_name' => 'Plain pen']);
        ItemReport::factory()->create(['item_name' => '100% cotton shirt']);
        $this->get('/items?q=%25')->assertSee('100% cotton shirt')->assertDontSee('Plain pen');
        $this->get('/items?q=_')->assertDontSee('Plain pen');
    }

    public function test_sql_injection_attempt_is_harmless(): void
    {
        ItemReport::factory()->create(['item_name' => 'Safe item']);
        $this->get("/items?q=".urlencode("' OR 1=1 --"))->assertOk()->assertDontSee('Safe item');
        $this->assertSame(1, ItemReport::count());
    }

    public function test_item_names_are_escaped_in_output(): void
    {
        ItemReport::factory()->create(['item_name' => '<script>alert(1)</script>']);
        $this->get('/items')->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }
}
