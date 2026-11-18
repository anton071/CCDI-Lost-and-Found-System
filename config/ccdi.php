<?php

return [
    // Lost reports contain no third-party data, so they are listed straight away by default.
    // Found reports ALWAYS wait for administrator review (thesis: verification before release).
    'auto_publish_lost' => (bool) env('REPORTS_AUTO_PUBLISH_LOST', true),

    'uploads' => [
        'disk' => 'local',                 // private disk: files are served only through an authorised route
        'directory' => 'item-images',
        // Maximum photo size in KB (30720 = 30 MB). PHP's upload_max_filesize / post_max_size
        // must be at least this large too - see README "Upload size limit".
        'max_kb' => (int) env('UPLOAD_MAX_KB', 30720),
        // Safety cap on decoded size: a 30 MB JPEG can be huge in pixels and GD needs ~5 bytes per pixel.
        'max_pixels' => (int) env('UPLOAD_MAX_PIXELS', 100_000_000),
        'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        'max_dimension' => 2048,
    ],
];
