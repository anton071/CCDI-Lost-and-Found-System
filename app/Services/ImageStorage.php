<?php

namespace App\Services;

use App\Models\ItemImage;
use App\Models\ItemReport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/*
 * Stores an uploaded photograph safely:
 *  - verifies the bytes really are a JPEG/PNG/WebP image (not just the extension / client MIME),
 *  - re-encodes it with GD (drops EXIF/GPS metadata and any embedded payload),
 *  - writes it under a random server-chosen name on the PRIVATE disk.
 * The client-supplied filename is kept only as display metadata and is never used as a path.
 */
class ImageStorage
{
    public function store(UploadedFile $file, ItemReport $report, ?User $uploader, bool $primary = true): ItemImage
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('The PHP GD extension is required to process uploaded photographs. Enable extension=gd in php.ini.');
        }

        $path = $file->getRealPath();
        $info = $path ? @getimagesize($path) : false;
        $allowed = config('ccdi.uploads.allowed_mimes');

        if ($info === false || ! in_array($info['mime'] ?? '', $allowed, true)) {
            throw new RuntimeException('The uploaded file is not a valid JPEG, PNG or WebP image.');
        }

        $pixels = (int) ($info[0] ?? 0) * (int) ($info[1] ?? 0);
        if ($pixels > (int) config('ccdi.uploads.max_pixels')) {
            throw new RuntimeException('That photograph has too many pixels to process ('.round($pixels / 1_000_000).' megapixels). Please use a smaller photo.');
        }
        $this->ensureMemoryFor($pixels);

        $source = @imagecreatefromstring((string) file_get_contents($path));
        if ($source === false) {
            throw new RuntimeException('The uploaded image could not be decoded.');
        }

        $source = $this->applyExifOrientation($source, $path, $info['mime']);
        $source = $this->fit($source, (int) config('ccdi.uploads.max_dimension'));

        // Flatten transparency onto white so PNG/WebP with alpha become a normal JPEG.
        $w = imagesx($source);
        $h = imagesy($source);
        $canvas = imagecreatetruecolor($w, $h);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $source, 0, 0, 0, 0, $w, $h);

        ob_start();
        imagejpeg($canvas, null, 88);
        $bytes = (string) ob_get_clean();

        $relative = trim(config('ccdi.uploads.directory'), '/').'/'.now()->format('Y/m').'/'.Str::uuid().'.jpg';
        Storage::disk(config('ccdi.uploads.disk'))->put($relative, $bytes);

        return ItemImage::create([
            'item_report_id' => $report->id,
            'uploaded_by' => $uploader?->id,
            'file_path' => $relative,
            'original_filename' => Str::limit(preg_replace('/[^\w.\- ]+/u', '_', basename((string) $file->getClientOriginalName())), 200, ''),
            'mime_type' => 'image/jpeg',
            'file_size_bytes' => strlen($bytes),
            'sha256_hash' => hash('sha256', $bytes),
            'is_primary' => $primary,
        ]);
    }

    public function absolutePath(ItemImage $image): string
    {
        return Storage::disk(config('ccdi.uploads.disk'))->path($image->file_path);
    }

    /** Decoding large photos with GD is memory-hungry (~5 bytes/pixel, plus copies); raise the limit for this request if needed. */
    private function ensureMemoryFor(int $pixels): void
    {
        $limit = ini_get('memory_limit');
        if ($limit === '-1' || $limit === false) {
            return;
        }
        $bytes = (int) $limit;
        $unit = strtolower(substr(trim((string) $limit), -1));
        $bytes *= match ($unit) { 'g' => 1073741824, 'm' => 1048576, 'k' => 1024, default => 1 };
        $needed = $pixels * 12 + 64 * 1048576;
        if ($bytes < $needed) {
            @ini_set('memory_limit', (string) min($needed, 1073741824));
        }
    }

    private function fit(\GdImage $img, int $max): \GdImage
    {
        $w = imagesx($img);
        $h = imagesy($img);
        if (max($w, $h) <= $max) {
            return $img;
        }
        $scale = $max / max($w, $h);

        return imagescale($img, (int) round($w * $scale), (int) round($h * $scale));
    }

    private function applyExifOrientation(\GdImage $img, string $path, string $mime): \GdImage
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data($path);
        $rotate = match ($exif['Orientation'] ?? 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $rotate ? (imagerotate($img, $rotate, 0) ?: $img) : $img;
    }
}
