<?php

namespace Koderia\SmsLive247;

use Illuminate\Support\ServiceProvider;
use Illuminate\Notifications\ChannelManager;
use Koderia\SmsLive247\Channels\SmsLive247Channel;

class SmsLive247ServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/smslive247.php', 'smslive247');

        $this->app->singleton('smslive247', function ($app) {
            $config = $app['config']->get('smslive247', []);

            // Seamless fallback if user still has legacy config in services.php
            if (empty($config['owner_email']) && $app['config']->has('services.smslive')) {
                $legacy = $app['config']->get('services.smslive', []);
                $config['base_url'] = $legacy['base_url'] ?? $config['base_url'];
                $config['owner_email'] = $legacy['owner_email'] ?? null;
                $config['subaccount'] = $legacy['subaccount'] ?? null;
                $config['subaccount_password'] = $legacy['subaccount_password'] ?? null;
            }

            return new SmsLive247Client($config);
        });

        $this->app->alias('smslive247', SmsLive247Client::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/smslive247.php' => config_path('smslive247.php'),
            ], 'smslive247-config');
        }

        // Register custom notification channel driver
        if ($this->app->bound(ChannelManager::class)) {
            $this->app->make(ChannelManager::class)->extend('smslive247', function ($app) {
                return $app->make(SmsLive247Channel::class);
            });
        }
    }
}
