<?php

namespace Spatie\Geocoder;

use Illuminate\Support\ServiceProvider;
use Spatie\Geocoder\Google\Geocoder;
use GuzzleHttp\Client;

class GeocoderServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../../config/geocoder.php' => config_path('geocoder.php'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../../../config/geocoder.php', 'geocoder');

        $this->app->bind('geocoder', function ($app) {
            return (new Geocoder(new Client))
                ->setKey(config('geocoder.key'))
                ->setLanguage(config('geocoder.language'))
                ->setRegion(config('geocoder.region'));
        });
    }
}
