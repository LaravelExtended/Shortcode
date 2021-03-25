<?php

namespace LaravelExtended\Shortcode\Facades;

/**
 * ----------------------------------------------------
 * Inject Shortcode to Service Container.
 * ----------------------------------------------------
 * @class     Shortcode
 * @package   LaravelExtended\Shortcode
 * --------------------------------------------------*/

use Illuminate\Support\Facades\Facade;

class Shortcode extends Facade
{
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() : string
	{
		return 'shortcode';
	}

}
