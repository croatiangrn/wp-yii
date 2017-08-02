<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\web;


use Rnd;
use rnd\base\Component;
use rnd\helpers\ArrayHelper;
use rnd\widgets\NavWalker;

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
	/**
	 * Website's default language
	 *
	 * @var string
	 */
	protected $defaultLanguage = 'en';
	protected $language = null;
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		if ( $this->viewName === null ) {
			$this->viewName = $this->createViewName();
		}
		$this->setLanguage();
		$this->setPageID();
		$this->setPageTitle();
	}

	/**
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
	 * Setter method for language
	 * Uses default PolyLang function and returns the slug of language
	 */
	protected function setLanguage()
	{
		$this->language = Rnd::$app->language;
	}

	/**
	 * Gets current language from the website
	 * @return string
	 */
	protected function getLanguage()
	{
		return Rnd::$app->language;
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

	/**
	 * Getter method for page ID
	 *
	 * @return int
	 */
	protected function getPageID()
	{
		return get_queried_object_id();
	}

	/**
	 * Returns menu
	 *
	 * @param array $args  Arguments for renderMenu method
	 *
	 * @return mixed
	 */
	public function renderMenu($args = [] )
	{
		$defaults = [
			'theme_location'  => 'topmenu',
			'container_id'    => 'navbar-header',
			'container_class' => 'collapse navbar-collapse',
			'menu_class'      => 'nav navbar-nav',
			'echo'            => false
		];

		$defaults = ArrayHelper::merge($defaults, $args);

		return wp_nav_menu( [
			'theme_location'  => $defaults['theme_location'],
			'container_id'    => $defaults['container_id'],
			'container_class' => $defaults['container_class'],
			'menu_class'      => $defaults['menu_class'],
			'walker'          => new NavWalker(),
			'echo'            => $defaults['echo'],
		] );
	}

	/**
	 * Getter method for page title
	 *
	 * @param $withBlogInfoName bool Whether to show title with site name or not
	 *
	 * @return mixed
	 */
	public function getPageTitle($withBlogInfoName = true)
	{
		if ($withBlogInfoName) {
			return $this->headerParams['title'];
		}

		return $this->headerParams['title-wpn'];
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
	 * Renders a view file as a PHP script.
	 *
	 * This method treats the view file as a PHP script and includes the file.
	 * It extracts the given parameters and makes them available in the view file.
	 * The method captures the output of the included view file and returns it as a string.
	 *
	 * This method should mainly be called by view renderer or [[renderFile()]].
	 *
	 * @param string    $_file_     absolute path to the view file.
	 * @param array     $_params_   (name-value pairs) that will be extracted and made available in the view file.
	 *
	 * @return string   the rendering result
	 * @throws \Exception
	 * @throws \Throwable
	 */
	public function renderPhpFile($_file_, $_params_ = [])
	{
		$_obInitialLevel_ = ob_get_level();
		ob_start();
		ob_implicit_flush(false);
		extract($_params_, EXTR_OVERWRITE);
		try {
			require($_file_);
			return ob_get_clean();
		} catch (\Exception $e) {
			while (ob_get_level() > $_obInitialLevel_) {
				if (!@ob_end_clean()) {
					ob_clean();
				}
			}
			throw $e;
		} catch (\Throwable $e) {
			while (ob_get_level() > $_obInitialLevel_) {
				if (!@ob_end_clean()) {
					ob_clean();
				}
			}
			throw $e;
		}
	}

	/**
	 * This method renders header, sets page title, etc..
	 *
	 * @see render()
	 */
	protected function renderHeader()
	{
		$this->headerParams = ArrayHelper::merge( [ 'title' => $this->getPageTitle() ], $this->headerParams );
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

		$themeRoot = Rnd::getAlias('@themeroot');

		foreach ( $this->sections as $section ) {
			$sectionFile = $themeRoot . '/views/' . $pageName . '/section-' . $section . '.php';
			$this->renderPhpFile($sectionFile);
		}
	}

	/**
	 * This method renders footer
	 *
	 * @param array $params
	 */
	protected function renderFooter( $params = [] )
	{
		$themeRoot = Rnd::getAlias('@themeroot');
		extract( $params );
		if ( file_exists( $footerFile = $themeRoot . '/views/common/footer.php' ) ) {
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