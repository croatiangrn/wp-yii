<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\web;


use Rnd;
use rnd\base\Component;

class Controller extends Component
{
	/**
	 * Template sections
	 * @var array $sections
	 */
	protected $sections = [];
	/**
	 * Params that are going to be used in header, for example title, etc..
	 * @var array $headerParams
	 */
	protected $headerParams = [];
	/**
	 * Params that are going to be used in body section
	 * @var array $bodyParams
	 */
	protected $bodyParams = [];
	/**
	 * Languages that are available on website
	 * @var array $allowedLanguages
	 */
	protected $allowedLanguages = [
	];
	protected $locale;
	protected $language;
	/**
	 * Current page/post ID
	 *
	 * @var null|int
	 */
	public $pageID = null;
	/**
	 * Defines where will controller look for sections
	 * Example: 'home'
	 *
	 * It will search for sections in <theme>/views/home/
	 *
	 * @var string $viewName
	 */
	protected $viewName = null;

	public function init()
	{
		$req = new Request();
		if ( $this->viewName === null ) {
			$this->viewName = $this->createViewName();
		}
		$this->setPageID();
		$this->setPageTitle();
	}

	/**
	 *
	 *
	 * @return mixed
	 */
	protected function createViewName() {
		$reflect = new \ReflectionClass($this);
		$input = $reflect->getShortName();
		preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
		$ret = $matches[0];
		foreach ($ret as &$match) {
			$match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
		}
		$str = implode('-', $ret);
		return str_replace('-controller', '', $str);
	}

	/**
	 * Gets current language from the website
	 * @return string
	 */
	protected function getLanguage()
	{
		return $this->language;
	}

	/**
	 * Sets current page ID to a property
	 *
	 * @see init()
	 */
	protected function setPageID()
	{
		$this->pageID = $this->getPageID();
	}

	protected function getPageID()
	{
		return get_queried_object_id();
	}

	/**
	 * Returns menu
	 *
	 * @param string $theme_location  Theme location that must be previously defined in themes 'functions.php'
	 * @param string $container_id    Optional, id of container div
	 * @param string $container_class Optional, class of container div
	 * @param string $menu_class      Optional, class of <ul>
	 *
	 * @return mixed
	 */
	public function renderMenu( $theme_location = 'topmenu', $container_id = 'navbar-header', $container_class = 'collapse navbar-collapse', $menu_class = 'nav navbar-nav' )
	{
		return wp_nav_menu( [
			'theme_location'  => $theme_location,
			'container_id'    => $container_id,
			'container_class' => $container_class,
			'menu_class'      => $menu_class,
			'echo'            => false,
		] );
	}

	/**
	 * Getter method for page title
	 * @return mixed
	 */
	protected function getPageTitle()
	{
		return $this->headerParams['title'];
	}

	/**
	 * Setter method for page title
	 */
	protected function setPageTitle()
	{
		$title_field = get_field( 'page_title_' . $this->language, $this->pageID );
		if ($title_field) {
			$this->headerParams['title'] = $title_field . ' - ' . get_bloginfo('name');
			$this->headerParams['title-wpn'] = $title_field;
		} else {
			$this->headerParams['title'] = get_the_title() . ' - ' . get_bloginfo('name');
			$this->headerParams['title-wpn'] = get_the_title();
		}
	}

	/**
	 * Getter method for header params
	 * @return array
	 */
	protected function getHeaderParams()
	{
		return $this->headerParams;
	}

	/**
	 * This method renders header, sets page title, etc..
	 *
	 * @see render()
	 */
	protected function renderHeader()
	{
		$this->headerParams = array_merge( [ 'title' => $this->getPageTitle() ], $this->headerParams );
		extract( $this->headerParams );

		$themeRoot = Rnd::getAlias('@themeroot');

		if ( file_exists( $themeRoot . '/views/' . $this->viewName . '/header.php' ) ) {
			include( $themeRoot . '/views/' . $this->viewName . '/header.php' );
		} else {
			include( $themeRoot . '/views/common/header.php' );
		}
	}

	/**
	 * This method renders body sections
	 *
	 * @see render()
	 */
	protected function renderBody()
	{
		$pageName = $this->viewName;
		extract( $this->bodyParams );

		foreach ( $this->sections as $section ) {
			if ( file_exists( $sectionFile = $this->themePath . '/views/' . $pageName . '/section-' . $section . '.php' ) ) {
				include( $sectionFile );
			}
		}
	}

	/**
	 * This method renders footer
	 *
	 * @param array $params
	 */
	protected function renderFooter( $params = [] )
	{
		extract( $params );
		if ( file_exists( $footerFile = $this->themePath . '/views/common/footer.php' ) ) {
			include( $footerFile );
		}
	}

	/**
	 * @param string $slug WordPress Page slug
	 * @param array  $args Associative array for params
	 *
	 * @return false|string
	 */
	public function createPageLink( $slug, $args = [] )
	{
		$link = get_permalink(get_page_by_path($slug));

		return ! empty( $args ) ? add_query_arg( $args, $link ) : $link;
	}

	/**
	 * Renders current view
	 *
	 * @param bool $renderFooter Whether footer should be rendered or not
	 */
	public function render( $renderFooter = true )
	{
		$this->renderHeader();
		$this->renderBody();
		if ( $renderFooter ) {
			$this->renderFooter();
		}
	}
}