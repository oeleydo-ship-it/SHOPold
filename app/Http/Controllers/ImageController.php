<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Resized WebP copies of images in public storage, for product cards and lists: /img/{width}/{path}.
 * Only a fixed set of widths is offered and only files inside storage/app/public are read, so the endpoint
 * cannot be used to read other files or to fill the disk with arbitrary sizes. Copies are made once and kept in
 * storage/app/public/thumbs (made again when the original changes).
 */
class ImageController extends Controller
{
    public const WIDTHS = [160, 320, 480, 640, 960, 1280];

    protected const SOURCE_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /** Originals larger than this many pixels are served as they are (decoding them would use too much memory). */
    protected const MAX_PIXELS = 40_000_000;

    public function show(int $width, string $path): BinaryFileResponse
    {
        abort_unless(in_array($width, self::WIDTHS, true), 404);

        $root = realpath(Storage::disk('public')->path(''));
        $source = realpath($root . DIRECTORY_SEPARATOR . str_replace(['\\', "\0"], ['/', ''], $path));
        abort_unless($root && $source && str_starts_with($source, $root . DIRECTORY_SEPARATOR) && is_file($source), 404);
        abort_unless(in_array(strtolower(pathinfo($source, PATHINFO_EXTENSION)), self::SOURCE_TYPES, true), 404);
        abort_if(str_starts_with($source, $root . DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR), 404);

        $relative = substr($source, strlen($root) + 1);
        $target = $root . DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR . $width . DIRECTORY_SEPARATOR . $relative . '.webp';

        if (!is_file($target) || filemtime($target) < filemtime($source)) {
            $size = @getimagesize($source);
            // animated GIFs would lose their animation
            if (!$size || $size[0] * $size[1] > self::MAX_PIXELS || $size[2] === IMAGETYPE_GIF) {
                return $this->send($source);
            }
            if (!is_dir(dirname($target))) {
                mkdir(dirname($target), 0755, true);
            }
            $image = (new ImageManager(new Driver()))->read($source);
            if ($image->width() > $width) {
                $image->scale(width: $width);
            }
            $image->toWebp(80)->save($target);
        }

        return $this->send($target);
    }

    /** URL of the resized copy of a public-storage path (the original's URL for anything else). */
    public static function url(?string $path, int $width): ?string
    {
        if (!$path) {
            return null;
        }
        if (preg_match('#^(https?:)?//#i', $path) || !preg_match('/\.(jpe?g|png|webp)$/i', $path)) {
            return str_starts_with($path, 'http') ? $path : asset('storage/' . ltrim($path, '/'));
        }

        return url("img/{$width}/" . implode('/', array_map('rawurlencode', explode('/', ltrim($path, '/')))));
    }

    protected function send(string $file): BinaryFileResponse
    {
        return response()->file($file, ['Cache-Control' => 'public, max-age=2592000']);
    }
}
