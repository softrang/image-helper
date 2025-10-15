<?php

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Resize & save image (without any external package)
 */
if (!function_exists('upload_image')) {
    function upload_image(UploadedFile $file, string $dir = 'uploads', array $options = []): string
    {
        $allowed = $options['allowed'] ?? ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, $allowed, true)) {
            throw new InvalidArgumentException("Invalid file type: .$ext");
        }

        $dir = trim($dir, '/');
        $targetPath = public_path($dir);

        if (!is_dir($targetPath)) {
            mkdir($targetPath, 0755, true);
        }

        $baseName = $options['name']
            ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $slug = Str::slug($baseName) ?: 'file';
        $filename = $slug . '-' . Str::random(10) . '.' . $ext;

        $width = $options['width'] ?? null;
        $height = $options['height'] ?? null;

        $tmpPath = $file->getRealPath();

        // Create image resource
        $source = match ($ext) {
            'jpeg', 'jpg' => imagecreatefromjpeg($tmpPath),
            'png' => imagecreatefrompng($tmpPath),
            'gif' => imagecreatefromgif($tmpPath),
            'webp' => imagecreatefromwebp($tmpPath),
            default => throw new InvalidArgumentException("Unsupported image type: $ext"),
        };

        $origWidth = imagesx($source);
        $origHeight = imagesy($source);

        // ✅ Auto width/height maintain
        if ($width && $height) {
            $newWidth = $width;
            $newHeight = $height;
        } elseif ($width) {
            $newWidth = $width;
            $newHeight = intval(($origHeight / $origWidth) * $width);
        } elseif ($height) {
            $newHeight = $height;
            $newWidth = intval(($origWidth / $origHeight) * $height);
        } else {
            $newWidth = $origWidth;
            $newHeight = $origHeight;
        }

        $canvas = imagecreatetruecolor($newWidth, $newHeight);

        // ✅ Transparent PNG/WebP support
        if (in_array($ext, ['png', 'webp'])) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
            imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // ✅ Resize
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        $savePath = $targetPath . '/' . $filename;

        // ✅ Quality optimization
        $targetMin = 15 * 1024; // 15 KB
        $targetMax = 30 * 1024; // 30 KB
        $quality = 90; // start with high quality

        do {
            ob_start();
            match ($ext) {
                'jpeg', 'jpg' => imagejpeg($canvas, null, $quality),
                'png' => imagepng($canvas, null, 9 - round($quality / 10)), // inverse scale
                'webp' => imagewebp($canvas, null, $quality),
                default => imagejpeg($canvas, null, $quality),
            };
            $imageData = ob_get_clean();
            $size = strlen($imageData);

            // Adjust quality based on size
            if ($size > $targetMax && $quality > 40) {
                $quality -= 10;
            } elseif ($size < $targetMin && $quality < 95) {
                $quality += 5;
            } else {
                break;
            }
        } while (true);

        file_put_contents($savePath, $imageData);

        imagedestroy($canvas);
        imagedestroy($source);

        return $dir . '/' . $filename;
    }
}


/**
 * Delete image from public path
 */
if (!function_exists('delete_image')) {
    function delete_image(?string $relativePath): bool
    {
        if (!$relativePath) return false;

        $full = public_path(ltrim($relativePath, '/'));
        return file_exists($full) ? @unlink($full) : false;
    }
}

/**
 * Update image (delete old + upload new)
 */
if (!function_exists('update_image')) {
    function update_image(?UploadedFile $newFile, ?string $oldPath = null, string $dir = 'uploads', array $options = []): ?string
    {
        if ($newFile instanceof UploadedFile) {
            if ($oldPath) delete_image($oldPath);
            return upload_image($newFile, $dir, $options);
        }
        return $oldPath;
    }
}
