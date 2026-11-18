<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\Category;
use App\Models\ItemRelease;
use App\Models\ItemReport;

class HomeController extends Controller
{
    public function index()
    {
        return view('home', [
            'stats' => [
                'lost' => ItemReport::published()->ofType(ReportType::Lost)->count(),
                'found' => ItemReport::published()->ofType(ReportType::Found)->count(),
                'returned' => ItemRelease::count(),
            ],
            'recent' => ItemReport::published()->with(['category', 'primaryImage'])->latest('id')->limit(6)->get(),
            'categories' => Category::active()->orderBy('name')->get(),
        ]);
    }
}
