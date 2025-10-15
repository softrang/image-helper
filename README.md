
##  Laravel Image Upload, Update & Delete Helper (by Softrang)
```
A simple and developer-friendly helper for image upload, update, and delete in Laravel 12+, created by [Softrang](https://softrang.com).
```

<ul>
    <li>Upload images with optional resize (width & height)</li>
    <li>Update images (automatically deletes old image)</li>
    <li>Delete images safely from the public folder</li>
    <li>Optimize image quality and size (target 15–30 KB)</li>
    <li>Support for JPEG, PNG, GIF, WebP formats</li>
</ul>

## 1️⃣ Install via Composer

```bash
composer require softrang/image-helper
```

## 2️⃣ Upload Image
##### Upload an image with optional width and height. The function automatically resizes and optimizes the image.

```bash
$path = upload_image($request->file('image'), 'products', [
    'width' => 300,  // Optional: Resize width
    'height' => 300, // Optional: Resize height
]);

// Save path to your database
Product::create([
    'name' => $request->name,
    'image' => $path
]);          
```


## 3️⃣ Update Image
##### Update an image. This function automatically deletes the old image from the public folder and uploads the new one with optional resize.
```bash
$path = update_image($request->file('image'), $request->old_image, 'products', [
    'width' => 300,  // Optional: Resize width
    'height' => 300, // Optional: Resize height
]);

// Update your database with new path
$product->update([
    'image' => $path
]);

```
## 4️⃣ Delete Image

```bash
$image = $request->image; // Get image path from database
delete_image($image);

```

## 5️⃣ Supported Image Formats

<ul>
    <li>jpg / jpeg</li>
    <li>png (supports transparency)</li>
    <li>gif</li>
    <li>webp (supports transparency)</li>
</ul>

## 6️⃣ Features

<ul>
    <li>✅ Resize Images with optional width & height</li>
    <li>✅ Quality Optimization to keep images between 15–30 KB</li>
    <li>✅ Transparency Support for PNG & WebP</li>
    <li>✅ Auto Delete Old Image on update</li>
    <li>✅ Easy to Integrate in Laravel 12+ projects</li>
    <li>✅ Standalone: No external packages required</li>
</ul>


## 7️⃣ Example Controller

```bash
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        $path = upload_image($request->file('image'), 'products', [
            'width' => 300,
            'height' => 300
        ]);

        return response()->json(['uploaded' => $path]);
    }

    public function update(Request $request)
    {
        $path = update_image($request->file('image'), $request->old_image, 'products', [
            'width' => 300,
            'height' => 300
        ]);

        return response()->json(['updated' => $path]);
    }

    public function destroy(Request $request)
    {
        delete_image($request->image);

        return response()->json(['deleted' => true]);
    }
}

```

## 8️⃣ Notes

<ul>
    <li>Automatically creates directories if they don’t exist</li>
    <li>Optimized for performance and small file size</li>
    <li>Easy to customize allowed file types</li>
</ul>


## 9️⃣ License
```
MIT License – free to use, modify, and distribute.
```

## 1️⃣0️⃣ How to Display the Image

#### After storing the image path in your database, you can display it in Blade like this:

```bash
@php
    $image = $request->image; // or from your database column
@endphp

<img src="{{ asset($image) }}" alt="Uploaded Image">
```
## Note:
<ul>
    <li>If your images are stored inside <code>public/uploads</code>, and your database stores <code>'uploads/filename.jpg'</code>, then <code>asset($image)</code> will generate the correct URL.</li>
    <li>If your images are stored inside subdirectories of <code>public/uploads</code> (like <code>public/uploads/subfolder/filename.jpg</code>), and your database does not store the full path, then <code>asset($image)</code> may not generate the correct URL.</li>
    <li>Do <strong>not</strong> prepend <code>'uploads/'</code> again if your database path already contains it.</li>
</ul>





