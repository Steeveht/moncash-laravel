<?php

namespace Steeve\MonCashLaravel;

use Illuminate\Support\ServiceProvider;

class MonCashServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        // 1. On fusionne la configuration par défaut
        $this->mergeConfigFrom(
            __DIR__ . '/../config/moncash.php',
            'moncash'
        );

        // 2. On lie la classe principale au conteneur de services Laravel
        $this->app->singleton('moncash', function ($app) {
            $configData = $app['config']['moncash'] ?? [];

            $config = new \Steeve\MonCashLaravel\Sdk\Config(
                $configData['mode'] ?? 'sandbox',
                $configData['client_id'] ?? '',
                $configData['client_secret'] ?? '',
                $configData['timeout'] ?? 60
            );

            $client = new \GuzzleHttp\Client([
                'timeout' => $config->getTimeout(),
            ]);

            $auth = new \Steeve\MonCashLaravel\Sdk\MonCashAuth($config, $client);

            $payment = new \Steeve\MonCashLaravel\Sdk\MonCashPayment($config, $auth, $client);
            $business = new \Steeve\MonCashLaravel\Sdk\MonCashBusiness($config, $auth, $client);
            $customer = new \Steeve\MonCashLaravel\Sdk\MonCashCustomer($config, $auth, $client);

            return new MonCash($payment, $business, $customer, $auth);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // 3. On permet à l'utilisateur de publier le fichier de config
        // via la commande: php artisan vendor:publish
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/moncash.php' => config_path('moncash.php'),
            ], 'moncash-config');
        }
    }
}
