<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\base;


use Rnd;
use rnd\web\Url;

abstract class AssetBundle extends Object
{
	/**
	 * @var array CSS files to be included in wp_head()
	 */
	public $css = [];
	/**
	 * @var array JS files to be included in wp_footer()
	 */
	public $js = [];
	/**
	 * @var string Current WP theme root
	 */
	protected $themeRoot;
	/**
	 * @var string Current WP theme url
	 */
	protected $themeUrl;

	public function publish()
	{
		foreach ( $this->css as $i => $cssarr ) {
			$name = uniqid('css-');
			if (is_array($cssarr)) {
				$deps = $cssarr['deps'] ?? [];
				$url  = $cssarr['url'];

				if (Url::isRelative( $url )) {
					wp_enqueue_style($name . '-css-' . $i, Rnd::getAlias('@themeurl/' . ltrim( $url, '/')), $deps, filemtime(Rnd::getAlias( '@themeroot/' . ltrim( $url, '/'))));
				} else {
					wp_enqueue_style($name . '-css-' . $i, $url, $deps, null);
				}
			} else {
				$url = $cssarr;
				$deps = [];
				if (Url::isRelative( $url )) {
					wp_enqueue_style($name . '-css-' . $i, Rnd::getAlias('@themeurl/' . ltrim( $url, '/')), $deps, filemtime(Rnd::getAlias( '@themeroot/' . ltrim( $url, '/'))));
				} else {
					wp_enqueue_style($name . '-css-' . $i, $url, $deps, null);
				}
			}
		}

		foreach ( $this->js as $j => $jsarr ) {
			$name = uniqid('js-');
			if (is_array($jsarr)) {
				$deps = $jsarr['deps'] ?? [];
				$url  = $jsarr['url'];
				$in_footer = $jsarr['in_footer'] ?? true;

				if (Url::isRelative( $url )) {
					wp_enqueue_script($name . '-js-' . $j, Rnd::getAlias('@themeurl/' . ltrim( $url, '/')), $deps, filemtime(Rnd::getAlias( '@themeroot/' . ltrim( $url, '/'))), $in_footer);
				} else {
					wp_enqueue_script($name . '-js-' . $j, $url, $deps, null, $in_footer);
				}
			} else {
				$url = $jsarr;
				$in_footer = true;
				$deps = [];
				if (Url::isRelative( $url )) {
					wp_enqueue_script($name . '-js-' . $j, Rnd::getAlias('@themeurl/' . ltrim( $url, '/')), $deps, filemtime(Rnd::getAlias( '@themeroot/' . ltrim( $url, '/'))), $in_footer);
				} else {
					wp_enqueue_script($name . '-js-' . $j, $url, $deps, null, $in_footer);
				}
			}

		}
	}

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		if ( $this->themeRoot === null){
			$this->themeRoot = \Rnd::getalias('@themeroot');
		}
		if ($this->themeUrl === null) {
			$this->themeUrl = \Rnd::getAlias( '@themeurl');
		}
	}
}