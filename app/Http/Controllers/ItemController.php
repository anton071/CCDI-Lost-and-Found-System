<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ItemReport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Public browsing and searching of PUBLISHED reports (works with or without photographs). */
class ItemController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(['lost', 'found'])],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'location' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $items = ItemReport::published()->filter($filters)
            ->with(['category', 'primaryImage'])
            ->latest('incident_date')->latest('id')
            ->paginate(12)->withQueryString();

        return view('items.index', [
            'items' => $items,
            'filters' => $filters,
            'categories' => Category::active()->orderBy('name')->get(),
        ]);
    }
}
