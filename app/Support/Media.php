<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Small helper around the "public" disk for uploaded thumbnails.
 *
 * Files are served through the /media/{path} route, so the project works
 * without `php artisan storage:link` (symlinks are unreliable on Windows) and
 * URLs are relative, so they don't depend on APP_URL.
 */
class Media
{
    public const DISK = 'public';

    public static function store(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, self::DISK);
    }

    /**
     * Store a new file and remove the previous one (if any).
     */
    public static function replace(?string $old, UploadedFile $file, string $directory): string
    {
        $path = self::store($file, $directory);
        self::delete($old);

        return $path;
    }

    public static function delete(?string $path): void
    {
        if ($path && Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public static function url(?string $path): ?string
    {
        return $path ? '/media/'.ltrim($path, '/') : null;
    }
}
