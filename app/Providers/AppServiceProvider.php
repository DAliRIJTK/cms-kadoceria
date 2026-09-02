<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->applyLocalHostGuard();
    }

    protected function applyLocalHostGuard(): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        try {
            $requestRoot = request()->root();
        } catch (\Throwable $e) {
            return;
        }

        if (! $requestRoot) {
            return;
        }

        $root = rtrim($requestRoot, '/');
        $configuredRoot = rtrim((string) env('APP_URL', $root), '/');

        $shouldOverride = empty($configuredRoot)
            || in_array($configuredRoot, ['http://localhost', 'http://127.0.0.1', 'http://127.0.0.1:8000', 'http://0.0.0.0', 'http://0.0.0.0:8000'], true);

        if (! $shouldOverride && ! app()->environment(['local', 'testing'])) {
            return;
        }

        config([
            'app.url' => $root,
            'app.asset_url' => $root,
            'filesystems.disks.public.url' => $root . '/storage',
        ]);

        URL::forceRootUrl($root);
    }
}
