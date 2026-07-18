<?php

namespace App\Providers;

use App\Models\AppNotification;
use App\Models\CourseClass;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Route::bind('notification', fn (string $value) => AppNotification::query()->findOrFail($value));
        Route::bind('class', fn (string $value) => CourseClass::query()->findOrFail($value));
    }
}
