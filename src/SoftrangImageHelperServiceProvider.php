<?php

namespace Softrang\ImageHelper;

use Illuminate\Support\ServiceProvider;

class SoftrangImageHelperServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // future publishing, optional
    }

    public function register()
    {
        require_once __DIR__ . '/Helpers/image_helper.php';
    }
}
