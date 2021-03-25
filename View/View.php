<?php

namespace LaravelExtended\Shortcode\View;

/**
 * ----------------------------------------------------
 * Implement the Illuminate\View class to create a
 * render method that allows the option to parse or
 * strip shortcodes.
 * ----------------------------------------------------
 * @class     View
 * @package   LaravelExtended\Shortcode
 * --------------------------------------------------*/

use LaravelExtended\Shortcode\Shortcode;
use Illuminate\View\View as IlluminateView;
use Illuminate\Contracts\View\Engine as EngineInterface;

class View extends IlluminateView
{
	/**
	 * @var Shortcode
	 *
	 * @public
	 */
	public $shortcode;

	/**
	 * View constructor.
	 *
	 * @param  Factory          $factory
	 * @param  EngineInterface  $engine
	 * @param  string           $view
	 * @param  string           $path
	 * @param  Shortcode        $shortcode
	 * @param  array            $data
	 */
	public function __construct(
		Factory         $factory,
		EngineInterface $engine,
		string          $view,
		string          $path,
		Shortcode       $shortcode,
		array           $data = []
	) {
		parent::__construct($factory, $engine, $view, $path, $data);

		$this->shortcode = $shortcode;
	}

	/**
	 * Compile view with shortcodes.
	 *
	 * @return $this
	 */
	public function withShortcodes() : self
	{
		$this->shortcode->mode = Shortcode::MODE_COMPILE;

		return $this;
	}

	/**
	 * Remove shortcodes from the view.
	 *
	 * @return $this
	 */
	public function withoutShortcodes() : self
	{
		$this->shortcode->mode = Shortcode::MODE_STRIP;

		return $this;
	}

	/**
	 * Retrieve and render the specified content.
	 * See Illuminate/View/View for base method.
	 *
	 * @return string
	 *
	 */
	protected function renderContents() : string
	{
		// Increment render count.
		$this->factory->incrementRender();

		// Compose the view.
		$this->factory->callComposer($this);

		// Get content from the view.
		$contents = $this->getContents();

		// Either compile the shortcode, or strip the
		// shortcode from the content. Stripping simply
		// destroys the shortcode.
		if ( $this->shortcode->mode === Shortcode::MODE_COMPILE ) {
			$contents = $this->shortcode->compile( $contents );
		} elseif ( $this->shortcode->mode === Shortcode::MODE_STRIP ) {
			$contents = $this->shortcode->destroy( $contents );
		}

		// Decrement render count.
		$this->factory->decrementRender();

		return $contents;
	}

}
