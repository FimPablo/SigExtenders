<?php

namespace FimPablo\SigExtenders;

use Illuminate\Support\ServiceProvider;

class YourPackageServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/Routes/api.php');
    }

    public function register()
    {
    }
}
