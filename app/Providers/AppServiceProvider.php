<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Product;
use App\Observers\ProductObserver;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * WHY register() vs boot()?
     *  - register() runs during the binding phase — only bind things into the IoC container here.
     *  - boot() runs after ALL providers are registered — safe to reference other services here.
     *    Observers, macros, and event listeners belong in boot().
     */
    public function register(): void
    {
        // Service bindings (e.g. interfaces → concrete classes) go here
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Tell Vite to prefetch 3 assets in parallel after the page loads — improves
        // subsequent page navigation speed without blocking the initial render.
        Vite::prefetch(concurrency: 3);

        // Register the ProductObserver so Eloquent fires it on every Product lifecycle event.
        // This is the single registration point — creating/updating/deleting hooks are
        // handled automatically from here on, project-wide.
        Product::observe(ProductObserver::class);
    }
}
