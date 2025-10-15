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
        if (!is_dir($targetPath)) mkdir($targetPath, 0755, true);

        $baseName = $options['name'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = Str::slug($baseName) ?: 'file';
        $filename = $slug . '-' . Str::random(10) . '.' . $ext;

        $width = $options['width'] ?? null;
        $height = $options['height'] ?? null;

        $tmpPath = $file->getRealPath();

        $createFunctions = [
            'jpg'  => 'imagecreatefromjpeg',
            'jpeg' => 'imagecreatefromjpeg',
            'png'  => 'imagecreatefrompng',
            'gif'  => 'imagecreatefromgif',
            'webp' => 'imagecreatefromwebp',
        ];

        $saveFunctions = [
            'jpg'  => fn($img, $path, $quality) => imagejpeg($img, $path, $quality),
            'jpeg' => fn($img, $path, $quality) => imagejpeg($img, $path, $quality),
            'png'  => fn($img, $path, $quality) => imagepng($img, $path, max(0, min(9, 9 - round($quality/10)))),
            'gif'  => fn($img, $path, $quality) => imagegif($img, $path),
            'webp' => fn($img, $path, $quality) => imagewebp($img, $path, $quality),
        ];

        if (!isset($createFunctions[$ext])) {
            throw new InvalidArgumentException("Unsupported image type: $ext");
        }

        $source = $createFunctions[$ext]($tmpPath);

        $origWidth = imagesx($source);
        $origHeight = imagesy($source);

        // Resize logic
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

        // Transparency
        if (in_array($ext, ['png','webp'])) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
            imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        // Quality optimization (15-30KB)
        $targetMin = 15*1024;
        $targetMax = 30*1024;
        $quality = 90;
        $maxIterations = 5;
        $imageData = '';

        for ($i = 0; $i < $maxIterations; $i++) {
            ob_start();
            $saveFunctions[$ext]($canvas, null, $quality);
            $imageData = ob_get_clean();
            $size = strlen($imageData);

            if ($size > $targetMax && $quality > 40) $quality -= 10;
            elseif ($size < $targetMin && $quality < 95) $quality += 5;
            else break;
        }

        file_put_contents($targetPath.'/'.$filename, $imageData);

        imagedestroy($canvas);
        imagedestroy($source);

        return $dir.'/'.$filename;
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
            // Delete old image if exists
            if ($oldPath) {
                $fullOldPath = public_path(ltrim($oldPath, '/'));
                if (file_exists($fullOldPath)) {
                    @unlink($fullOldPath);
                }
            }

            // Upload new image with resize & optimization
            return upload_image($newFile, $dir, $options);
        }

        // If no new file uploaded, keep old
        return $oldPath;
    }
}

