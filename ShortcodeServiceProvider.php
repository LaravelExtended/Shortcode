<?php

namespace LaravelExtended\Shortcode;

/**
 * ----------------------------------------------------
 * Shortcode Service Provider
 * ----------------------------------------------------
 * @class     ShortcodeServiceProvider
 * @package   LaravelExtended\Shortcode
 * --------------------------------------------------*/


use LaravelExtended\Shortcode\View\Factory;
use Illuminate\Support\ServiceProvider;

class ShortcodeServiceProvider extends ServiceProvider
{
	/**
	 * Register the service provider.
	 */
	public function register() : void
	{
		$this->registerShortcode();
		$this->registerView();
	}

	/**
	 * Register Shortcode singleton
	 */
	protected function registerShortcode() : void
	{
		$this->app->singleton('shortcode', static function() {
			return new Shortcode();
		});
	}

	/**
	 * Register the View
	 */
	protected function registerView() : void
	{
		$this->app->singleton('view', function( $app ) {

			$finder     = $app['view.finder'];
			$resolver   = $app['view.engine.resolver'];
			$env        = new Factory( $resolver, $finder, $app['events'], $app['shortcode'] );

			$env->setContainer($app);
			$env->share('app', $app);

			return $env;
		});
	}

	/**
	 * Get service providers.
	 *
	 * @return array
	 */
	public function provides() : array
	{
		return [ 'shortcode', 'view' ];
	}
}
