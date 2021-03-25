<?php

namespace LaravelExtended\Shortcode;

/**
 * ----------------------------------------------------
 * Base class for managing Shortcodes.
 * ----------------------------------------------------
 * @class     Shortcode
 * @package   LaravelExtended\Shortcode
 * --------------------------------------------------*/


class Shortcode
{
	/**
	 * Compile mode
	 *
	 * @const int
	 */
	public const MODE_COMPILE   = 1;

	/**
	 * Strip mode
	 *
	 * @const int
	 */
	public const MODE_STRIP     = 2;

	/**
	 * Shortcodes render mode.
	 *
	 * @var int
	 */
	public $mode;

	/**
	 * Container for storing shortcode tags.
	 *
	 * @var array
	 */
	protected $tags = [];

	/**
	 * Add shortcode tag.
	 *
	 * @param string $tag
	 * @param        $callback
	 *
	 * @return bool
	 */
	public function add( string $tag, $callback ) : bool
	{
		if ( ! $this->exists( $tag ) )
		{
			$this->tags[ $tag ] = $callback;
			return true;
		}

		return false;
	}

	/**
	 * Count the total tags registered.
	 *
	 * @return int
	 */
	public function count() : int
	{
		return count( $this->tags );
	}

	/**
	 * Check if the tag is registered.
	 *
	 * @param string $tag
	 *
	 * @return bool
	 */
	protected function exists( string $tag ) : bool
	{
		return array_key_exists( $tag, $this->tags );
	}

	/**
	 * Compile shortcodes found within content.
	 *
	 * @param $content
	 *
	 * @return mixed
	 */
	public function compile( string $content )
	{
		// If no tags exist, simply return the content.
		if ( ! $this->count() )
		{
			return $content;
		}

		// See method for a pattern of regex patterns.
		// They're based on WordPress's regex implementation.
		$pattern = $this->getRegex();

		// Match and replace content by expression pattern.
		return preg_replace_callback( "/$pattern/s", [ $this, 'render' ], $content );
	}

	/**
	 * Remove all shortcode tags from the given content.
	 *
	 * @param $content
	 *
	 * @return mixed
	 */
	public function destroy( string $content )
	{
		if ( ! $this->count() )
		{
			return $content;
		}

		$pattern = $this->getRegex();

		return preg_replace_callback("/$pattern/s", [$this, 'remove'], $content);
	}

	/**
	 * See `create` method, within `preg_replace_callback`
	 *
	 * @param $match
	 *
	 * @return bool|string
	 */
	protected function render( array $match )
	{
		if ( $match[ 1 ] === '[' && $match[ 6 ] === ']' )
		{
			return substr( $match[0], 1, -1 );
		}

		$tag    = $match[ 2 ];
		$attr   = $this->parseAttributes( html_entity_decode( $match[ 3 ], ENT_QUOTES ) );

		if ( isset( $match[ 5 ] ) )
		{
			return $match[ 1 ] . call_user_func( $this->tags[ $tag ], $attr, $match[ 5 ], $tag ) . $match[ 6 ];
		}

		return $match[ 1 ] . call_user_func( $this->tags[ $tag ], $attr, null, $tag ) . $match[ 6 ];
	}

	/**
	 * Retrieve the shortcode regular expression for searching.
	 * The patterns are loosely based on the WordPress patterns
	 * used for matching tags.
	 *
	 * @return string
	 */
	protected function getRegex() : string
	{
		$tagNames   = array_keys($this->tags);
		$tagRegExp  = implode( '|', array_map( 'preg_quote', $tagNames));

		return
			'\\['
			. '(\\[?)'
			. "($tagRegExp)"
			. '(?![\\w-])'
			. '('
			. '[^\\]\\/]*'
			. '(?:'
			. '\\/(?!\\])'
			. '[^\\]\\/]*'
			. ')*?'
			. ')'
			. '(?:'
			. '(\\/)'
			. '\\]'
			. '|'
			. '\\]'
			. '(?:'
			. '('
			. '[^\\[]*+'
			. '(?:'
			. '\\[(?!\\/\\2\\])'
			. '[^\\[]*+'
			. ')*+'
			. ')'
			. '\\[\\/\\2\\]'
			. ')?'
			. ')'
			. '(\\]?)';
	}

	/**
	 * Retrieve all attributes from the shortcodes tag.
	 *
	 * @param  string  $string
	 *
	 * @return array|string
	 */
	protected function parseAttributes( string $string )
	{
		$attributes = [];

		// 1. 00a0 = No Break Space
		// 2. 200b = Zero Width Space
		// Replace any erroneous spacing unicode characters with
		// proper utf-8 formatted spaces.
		$string = preg_replace( '/[\x{00a0}\x{200b}]+/u', ' ', $string );

		// Match any attribute, i.e., `id="1"`, that lives between
		// two brackets, i.e., `[` and `]`, and has a space between
		// the first bracket and the name preceding it, i.e., `[shortcode id="1"]` -
		// this will capture `id="1"` as a valid pattern when preg matched.
		$pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)'
			. '(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';

		if ( preg_match_all( $pattern, $string, $matched, PREG_SET_ORDER ) ) {

			// For each matched pattern, translate each key => value into
			// an array. For example, if match returns `id="1"`,
			// then the match will be split in to three array elements:
			//
			// 1. The original attribute, i.e., `id="1"`
			// 2. The attribute key, i.e., `id`
			// 3. The attribute value, i.e., `1`
			//
			// This will format in to an array like so:
			// [ "id" => "1" ]
			foreach ( $matched as $match )
			{

				if ( ! empty( $match[ 1 ] ) )
				{
					$attributes[ strtolower( $match[ 1 ] ) ] = stripcslashes( $match[ 2 ] );
				}

				elseif ( ! empty( $match[ 3 ] ) )
				{
					$attributes[ strtolower( $match[ 3 ] ) ] = stripcslashes( $match[ 4 ] );
				}

				elseif ( ! empty( $match[ 5 ] ) )
				{
					$attributes[ strtolower( $match[ 5 ] ) ] = stripcslashes( $match[ 6 ] );
				}

				elseif ( isset( $match[ 7 ] ) && $match[ 7 ] !== '' )
				{
					$attributes[] = stripcslashes( $match[ 7 ] );
				}

				elseif ( isset( $match[ 8 ] ) )
				{
					$attributes[] = stripcslashes( $match[ 8 ] );
				}
			}
		} else {
			$attributes = ltrim( $string );
		}

		return $attributes;
	}

	/**
	 * Merge set attributes with default attributes.
	 *
	 * @param $pairs
	 * @param $attributes
	 *
	 * @return array
	 */
	public static function setAttributes( array $pairs, array $attributes ) : array
	{
		$newAttributes = [];

		foreach ( $pairs as $name => $default ) {

			if ( array_key_exists( $name, $attributes ) ) {
				$newAttributes[ $name ] = $attributes[ $name ];
			} else {
				$newAttributes[ $name ] = $default;
			}
		}

		return $newAttributes;
	}

	/**
	 * Remove shortcode tag.
	 *
	 * @param array $match
	 *
	 * @return bool|string
	 */
	protected function remove( array $match )
	{
		// If tags match
		if ( $match[ 1]  === '[' && $match[ 6 ] === ']' )
		{
			return substr( $match[ 0 ], 1, -1 );
		}

		return $match[ 1 ] . $match[ 6 ];
	}

}
