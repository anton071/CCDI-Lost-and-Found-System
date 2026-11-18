<?php

namespace Tests;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();                       // views render without a compiled asset build
        config(['matching.enabled' => false]);      // opt in to the real matcher per test
    }

    protected function admin(array $attrs = []): User
    {
        return User::factory()->admin()->create($attrs);
    }

    protected function member(array $attrs = []): User
    {
        return User::factory()->create($attrs);
    }

    protected function category(): Category
    {
        return Category::factory()->create();
    }

    /** A real (tiny but valid) JPEG built with GD. */
    protected function jpeg(string $name = 'item.jpg', int $w = 300, int $h = 300): UploadedFile
    {
        $im = imagecreatetruecolor($w, $h);
        imagefill($im, 0, 0, imagecolorallocate($im, 120, 160, 200));
        imagefilledellipse($im, (int) ($w / 2), (int) ($h / 2), (int) ($w / 2), (int) ($h / 3), imagecolorallocate($im, 30, 30, 30));
        $path = tempnam(sys_get_temp_dir(), 'jpg');
        imagejpeg($im, $path, 90);

        return new UploadedFile($path, $name, 'image/jpeg', null, true);
    }
}
