<?php

namespace LaravelExtended\Shortcode\View;

/**
 * ----------------------------------------------------
 * Make view container and merge attributes. Then,
 * initiate the view contract to render the compiled
 * content, including the shortcode tag.
 * ----------------------------------------------------
 * @class     Factory
 * @package   LaravelExtended\Shortcode
 * --------------------------------------------------*/

use LaravelExtended\Shortcode\Shortcode;
use Illuminate\Events\Dispatcher;
use Illuminate\View\ViewFinderInterface;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory as IlluminateViewFactory;

class Factory extends IlluminateViewFactory
{
	/**
	 * @var Shortcode
	 *
	 * @public
	 */
	public $shortcode;

	/**
	 * @var $aliases
	 *
	 * @protected
	 */
	private $aliases;

	/**
	 * Factory constructor.
	 *
	 * @param EngineResolver      $engines
	 * @param ViewFinderInterface $finder
	 * @param Dispatcher          $events
	 * @param Shortcode           $shortcode
	 */
	public function __construct(
		EngineResolver      $engines,
		ViewFinderInterface $finder,
		Dispatcher          $events,
		Shortcode           $shortcode
	) {
		parent::__construct( $engines, $finder, $events );

		$this->shortcode = $shortcode;
	}

	/**
	 * Get the evaluated view contents for the given view.
	 *
	 * @param        $view
	 * @param array  $data
	 * @param array  $merge
	 *
	 * @return View
	 */
	public function make( $view, $data = [], $merge = [] ) : View
	{
		// If a view alias exists, use that instead.
		if ( isset( $this->aliases[ $view ] ) ) {
			$view = $this->aliases[ $view ];
		}

		// Normalize the view name
		$view = $this->normalizeName($view);

		// Get the fully-qualified path of the view
		$path = $this->finder->find($view);

		// Merge view array data
		$data = array_merge($merge, $this->parseData($data));

		// Create view
		$viewContract = new View($this, $this->getEngineFromPath($path), $view, $path, $this->shortcode, $data);

		// Dispatch view creator event, passing reference to IlluminateViewFactory,
		// the view path, the view name, the relative path, merged view data and
		// finally, the shortcode itself.
		$this->callCreator($viewContract);

		return $viewContract;
	}
}
