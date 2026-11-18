<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\Like;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $action = $request->validate(['action' => ['nullable', 'string', 'max:60']])['action'] ?? null;

        return view('admin.activity.index', [
            'logs' => ActivityLog::with('user')
                ->when($action, fn ($q) => Like::where($q, 'action', $action))
                ->latest('id')->paginate(25)->withQueryString(),
            'action' => $action,
        ]);
    }
}
