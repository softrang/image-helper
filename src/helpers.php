<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;
if (! function_exists('upload_image')) {
    /**
     * Upload an image directly into /public/{dir}
     *
     * @param UploadedFile $file
     * @param string       $dir
     * @param array        $options
     * @return string
     */
    function upload_image(UploadedFile $file, string $dir = 'uploads', array $options = []): string
    {
        $allowed = $options['allowed'] ?? ['jpg','jpeg','png','gif','webp'];
        $ext     = strtolower($file->getClientOriginalExtension());

        if (! in_array($ext, $allowed, true)) {
            throw new InvalidArgumentException("Invalid file type: .$ext");
        }

        $dir = trim($dir, '/');
        $targetPath = public_path($dir);

        if (! is_dir($targetPath)) {
            mkdir($targetPath, 0755, true);
        }

        $baseName = $options['name']
            ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $slug     = Str::slug($baseName) ?: 'file';
        $filename = $slug . '-' . Str::random(10) . '.' . $ext;

        $file->move($targetPath, $filename);

        return $dir . '/' . $filename;
    }
}

if (! function_exists('delete_image')) {
    function delete_image(?string $relativePath): bool
    {
        if (! $relativePath) return false;

        $full = public_path(ltrim($relativePath, '/'));
        if (file_exists($full)) {
            return @unlink($full);
        }
        return false;
    }
}

if (! function_exists('update_image')) {
    /**
     * Update an image: delete old if new uploaded
     *
     * @param UploadedFile|null $newFile
     * @param string|null       $oldPath
     * @param string            $dir
     * @param array             $options
     * @return string|null
     */
    function update_image(?UploadedFile $newFile, ?string $oldPath = null, string $dir = 'uploads', array $options = []): ?string
    {
       
        if ($newFile instanceof UploadedFile) {
         
            if ($oldPath) {
                delete_image($oldPath);
            }
        
            return upload_image($newFile, $dir, $options);
        }

    
        return $oldPath;
    }
}


