<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\widgets;


use rnd\base\Component;
use rnd\base\InvalidCallException;
use rnd\base\InvalidParamException;
use rnd\helpers\ArrayHelper;
use rnd\helpers\Html;

class Pagination extends Component
{
	/**
	 * @var array This will be populated using paginate_links() function
	 */
	public $paginate_links = [];
	/**
	 * @var string Class name for <ul> tag
	 */
	public $className = 'pagination';
	/**
	 * @var array Pagination args
	 */
	public $args = [];

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		$this->setPagination();
	}

	protected function setPagination()
	{
		$defaults = [
			'format'   => '?page=%#%',
			'current' => get_query_var('paged'),
			'total' => wp_count_posts(),
			'type' => 'array',
			'prev_next' => false
		];

		$new_args = ArrayHelper::merge($defaults, $this->args);

		$this->paginate_links = paginate_links($new_args);
	}

	/**
	 * Renders ul > li tags for pagination in bootstrap style
	 *
	 * @return string
	 */
	public function render()
	{
		$pages = $this->paginate_links;
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