<?php

namespace Altelma\LaravelSignRequest;

use Illuminate\Support\ServiceProvider;

class SignRequestServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/signrequest.php' => config_path('signrequest.php'),
        ], 'signrequest');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/signrequest.php', 'signrequest');

        $this->app->singleton('SignRequest', function ($app) {
            $config = $app->make('config');
            $token = $config->get('signrequest.token');
            $subdomain = $config->get('signrequest.subdomain');

            return new SignRequestService($token);
        });
    }

    public function provides()
    {
        return ['SignRequest'];
    }
}
