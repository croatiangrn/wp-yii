<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\widgets;


use rnd\base\Component;
use rnd\base\InvalidParamException;
use rnd\helpers\ArrayHelper;
use WP_Query;

class Posts extends Component
{
	/**
	 * @var array Arguments that'll be used for WP_Query
	 */
	public $post_args = [];
	/**
	 * @var string Query parameter for pagination
	 */
	public $query_param = 'paged';
	/**
	 * @var int Total number of posts
	 */
	protected $number_of_posts;
	/**
	 * @var int Current page number
	 */
	protected $current_page;
	/**
	 * @var Pagination
	 */
	private $_pagination;
	/**
	 * @var array Arguments that'll be used for Pagination configuration
	 */
	public $pagination_args = [];
	/**
	 * Queried posts
	 *
	 * @var WP_Query
	 */
	public $posts;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		if (! $this->isQueryParamValid()) {
			throw new InvalidParamException('Param `query_param` is not valid! Use "page" or "paged"');
		}
		$this->setCurrentPage();
		$this->setPosts();
		$this->setPagination();
	}

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
	 * Setter method for current page property
	 */
	protected function setCurrentPage() {
		$currentPage = ( get_query_var($this->query_param) ) ? get_query_var($this->query_param) : 1;
		$this->current_page = $currentPage;
	}

	/**
	 * Setter method for Pagination Class
	 */
	public function setPagination()
	{
		$defaults = [
			'total' => $this->posts->found_posts,
			'current' => $this->current_page
		];

		$new_args = ArrayHelper::merge( $defaults, $this->pagination_args );

		$this->_pagination = new Pagination($new_args);
	}

	/**
	 * Getter method for pagination
	 *
	 * @return Pagination
	 */
	public function getPagination()
	{
		return $this->_pagination;
	}

	/**
	 * Setter method for post arguments
	 */
	protected function setPosts()
	{
		$defaults = [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'paged'          => $this->current_page,
			'posts_per_page' => get_option( 'posts_per_page' ),
		];

		$new_args = ArrayHelper::merge( $defaults, $this->post_args );

		$this->post_args = $new_args;

		$this->posts = new WP_Query($this->post_args);
	}
}