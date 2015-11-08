<?php

namespace Krisawzm\CriticalCss;

use Illuminate\Support\ServiceProvider;
use Krisawzm\CriticalCss\Storage\LaravelStorage;
use Krisawzm\CriticalCss\HtmlFetchers\LaravelHtmlFetcher;
use Krisawzm\CriticalCss\CssGenerators\CriticalGenerator;

class CriticalCssServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->registerAppBindings();
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfig();

        if ($this->app['config']->get('criticalcss.blade_directive')) {
            BladeUtils::registerBladeDirective(
                $this->app['view']->getEngineResolver()->resolve('blade')->getCompiler()
            );
        }
    }

    /**
     * Set up the config.
     *
     * @return void
     */
    protected function setupConfig()
    {
        $src = realpath(__DIR__.'/config/criticalcss.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $src => config_path('criticalcss.php'),
            ]);
        }

        $this->mergeConfigFrom($src, 'criticalcss');
    }

    /**
     * Register Application bindings.
     *
     * @return void
     */
    protected function registerAppBindings()
    {
        $this->app->singleton('criticalcss.storage', function ($app) {
            return new LaravelStorage(
                $app['config']->get('criticalcss.storage'),
                $app->make('filesystem')->disk($app['config']->get('filesystems.default')),
                $app['config']->get('criticalcss.pretend')
            );
        });

        $this->app->singleton('criticalcss.htmlfetcher', function ($app) {
            return new LaravelHtmlFetcher(function () {
                return require base_path('bootstrap/app.php');
            });
        });

        // $this->app->singleton('criticalcss.htmlfetcher', function ($app) {
        //     return new LaravelHtmlFetcher;
        // });

        $this->app->singleton('criticalcss.cssgenerator', function ($app) {
            $generator = new CriticalGenerator(
                array_map('public_path', $app['config']->get('criticalcss.css')),
                $app->make('criticalcss.htmlfetcher'),
                $app->make('criticalcss.storage')
            );

            $generator->setCriticalBin(
                $app['config']->get('criticalcss.critical_bin')
            );

            $generator->setOptions(
                $app['config']->get('criticalcss.width'),
                $app['config']->get('criticalcss.height'),
                $app['config']->get('criticalcss.ignore')
            );

            return $generator;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function provides()
    {
        return [
            'criticalcss.storage',
            'criticalcss.htmlfetcher',
            'criticalcss.cssgenerator',
        ];
    }
}