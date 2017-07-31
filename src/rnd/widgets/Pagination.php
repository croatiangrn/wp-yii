<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\widgets;


use rnd\base\Component;
use rnd\base\InvalidCallException;
use rnd\base\InvalidParamException;
use rnd\helpers\Html;

class Pagination extends Component
{
	/**
	 * @var array This will be populated using paginate_links() function
	 */
	public $pages = [];
	/**
	 * @var string Can be 'page' or 'paged'
	 */
	public $query_param = 'page';
	/**
	 * @var int Current page number
	 */
	public $current_page;
	/**
	 * @var string Class name for <ul> tag
	 */
	public $className = 'pagination';
	/**
	 * Get this value by calling WP_Query($args)->max_num_posts
	 *
	 * @var int|null Total number of posts
	 */
	public $total = null;

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

		$this->setCurrentPage();

		$this->setPagination();
	}

	protected function setPagination()
	{
		$this->pages = paginate_links([
			'format'   => '?page=%#%',
			'current' => $this->current_page,
			'total' => $this->total,
			'type' => 'array',
			'prev_next' => false
		]);
	}

	/**
	 * Setter method for current page property
	 */
	protected function setCurrentPage() {
		$currentPage = ( get_query_var($this->query_param) ) ? get_query_var($this->query_param) : 1;
		$this->current_page = $currentPage;
	}

	/**
	 * Getter method for current page property
	 *
	 * @return int
	 */
	protected function getCurrentPage()
	{
		return $this->current_page;
	}

	/**
	 * Renders ul > li tags for pagination in bootstrap style
	 *
	 * @return string
	 */
	public function render()
	{
		$pages = $this->pages;
		if ($pages === null) {
			return false;
		}

		$pagination = Html::beginTag('ul', ['class' => $this->className]);
		foreach ( $pages as $k => $page ) {
			$pagination .= Html::tag('li', $page);
		}

		$pagination .= Html::endTag('ul');

		return $pagination;
	}

}