<?php

/*
 | Image-matching worker settings (SIFT first, ORB as fallback).
 |
 | The acceptance criteria below are DEFAULTS chosen as a starting point. They have not been
 | validated against CCDI belongings; tune them with your own test photographs and record the
 | values you finally use in the thesis (Chapter 3).
 |
 | A pair is accepted by an algorithm when
 |     keypoints (both images)  >= min_keypoints
 |     AND ratio-test matches that agree with one RANSAC homography ("inliers") >= min_inliers
 |     AND inliers / ratio-test matches >= min_inlier_ratio
 | ORB runs only when NO candidate satisfied the SIFT criterion.
 */
return [
    'enabled' => (bool) env('MATCHER_ENABLED', true),
    'python' => env('MATCHER_PYTHON', 'python'),
    'script' => base_path('scripts/matcher/match_images.py'),
    'timeout' => (int) env('MATCHER_TIMEOUT_SECONDS', 90),
    'cache_dir' => storage_path('app/private/features'),
    'allowed_root' => storage_path('app/private'),
    'max_candidates' => 200,

    'settings' => [
        'max_image_bytes' => 10 * 1024 * 1024,
        'max_dimension' => 1024,
        'sift' => [
            'n_features' => 1500,
            'ratio' => (float) env('MATCHER_SIFT_RATIO', 0.75),
            'ransac_px' => 5.0,
            'min_inliers' => (int) env('MATCHER_SIFT_MIN_INLIERS', 12),
            'min_inlier_ratio' => (float) env('MATCHER_SIFT_MIN_INLIER_RATIO', 0.25),
            'min_keypoints' => (int) env('MATCHER_MIN_KEYPOINTS', 20),
        ],
        'orb' => [
            'n_features' => 1500,
            'ratio' => (float) env('MATCHER_ORB_RATIO', 0.75),
            'ransac_px' => 5.0,
            'min_inliers' => (int) env('MATCHER_ORB_MIN_INLIERS', 15),
            'min_inlier_ratio' => (float) env('MATCHER_ORB_MIN_INLIER_RATIO', 0.25),
            'min_keypoints' => (int) env('MATCHER_MIN_KEYPOINTS', 20),
        ],
    ],
];
