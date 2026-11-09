<?php

namespace App\Http\Controllers;

use App\Support\Media;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Fallback for uploaded thumbnails (storage/app/public) when the public/media
 * link from `php artisan storage:link` is missing. With the link in place, the
 * web server serves the files directly and this controller isn't reached.
 */
class MediaController extends Controller
{
    public function __invoke(string $path): StreamedResponse
    {
        $disk = Storage::disk(Media::DISK);

        abort_if(str_contains($path, '..') || ! $disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'public, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
        ]);
    }
}
