<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\widgets;


use rnd\base\Component;
use rnd\base\InvalidParamException;
use rnd\helpers\Html;

class BootstrapPagination extends Component
{
	/**
	 * @var array This will be populated using paginate_links() function
	 */
	public $pages = [];
	/**
	 * @var string Can be 'page' or 'paged'
	 */
	protected $query_param = 'page';
	/**
	 * @var int Current page number
	 */
	protected $current_page = 0;
	/**
	 * @var string Class name for <ul> tag
	 */
	public $className = 'pagination';

	/**
	 * This method checks if query param is valid
	 *
	 * @return bool
	 */
	protected function isQueryParamValid()
	{
		return ($this->query_param == 'page' || $this->query_param == 'paged');
	}

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		if (! $this->isQueryParamValid()) {
			throw new InvalidParamException('Param `query_param` is not valid! Use "page" or "paged"');
		}

		if (! is_array($this->pages)) {
			throw new InvalidParamException('Param `pages` must be an array!');
		}
	}

	/**
	 * Setter method for current page property
	 */
	protected function setCurrentPage() {
		$currentPage = ( get_query_var($this->query_param) ) ? get_query_var($this->query_param) : 1;
		$this->current_page = $currentPage;
	}

	/**
	 * Renders ul > li tags for pagination in bootstrap style
	 *
	 * @return string
	 */
	public function render()
	{
		$pages = $this->pages;

		$pagination = Html::beginTag('ul', ['class' => $this->className]);
		foreach ( $pages as $k => $page ) {
			$pagination .= Html::tag('li', $page);
		}

		$pagination .= Html::endTag('ul');

		return $pagination;
	}

}