<?php

namespace App\Http\Controllers;

use App\Support\Media;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves uploaded thumbnails from storage/app/public.
 * (Avoids requiring `php artisan storage:link`, which needs symlink rights on Windows.)
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
