<?php

namespace App\Http\Controllers;

use App\Models\ItemImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/** Photographs live on a private disk and are served only after an authorisation check. */
class ImageController extends Controller
{
    public function show(Request $request, ItemImage $image)
    {
        $report = $image->report;
        abort_unless(Gate::forUser($request->user())->allows('viewImage', $report), 404);

        $disk = Storage::disk(config('ccdi.uploads.disk'));
        abort_unless($disk->exists($image->file_path), 404);

        return $disk->response($image->file_path, null, [
            'Content-Type' => 'image/jpeg',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
