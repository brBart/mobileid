<?php
/**
 * @copyright 2017 Kullar Kert
 * @license   https://opensource.org/licenses/MIT MIT
 */

namespace kullar84\mobileid;

use Illuminate\Support\ServiceProvider;

/**
 * MobileIDServiceProvider class for dealing Laravel ServiceProvider
 *
 * @author        Kullar Kert <kullar.kert@gmail.com>
 * @license       https://opensource.org/licenses/MIT MIT
 * @package       kullar84\MobileID
 * @copyright     2017 Kullar Kert
 */
class MobileIDServiceProvider extends ServiceProvider
{
    /**
     * Determine if the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__.'/locale', 'mobileid');

        $this->publishes(
            [
                __DIR__.'/../config/mobileid.php' => config_path('mobileid.php'),
            ], 'mobileid'
        );
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mobileid.php', 'mobileid');
        $this->app->singleton(
            'mobileid', function ($app) {
            $config = $app->make('config');

            $dev = $config->get('mobileid.dev');

            return new MobileIDAuthenticate($dev);
        }
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['mobileid'];
    }
}