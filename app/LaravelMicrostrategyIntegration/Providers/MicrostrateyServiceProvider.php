<?php

namespace App\LaravelMicrostrategyIntegration\Providers;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class MicrostrateyServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->bind('Strategy', function () {
            return Http::accept('application/json')->withOptions(
                [
                    'verify' => false,
                    'base_uri' => config('strategy.base_url')
                ]
            );


        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    { {
            $this->publishes([
                __DIR__ . 'config' => config_path('strategy.php'),
            ], 'laravelmicrostrategyintegration');
        }
    }
}
