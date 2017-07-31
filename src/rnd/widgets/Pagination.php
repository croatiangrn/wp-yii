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
	 * @var string Class name for <ul> tag
	 */
	public $className = 'pagination';
	/**
	 * @var int Total number of posts
	 */
	public $total;
	/**
	 * @var int Current page
	 */
	public $current_page;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
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