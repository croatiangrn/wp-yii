<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\widgets;


use rnd\base\Component;
use rnd\helpers\ArrayHelper;
use WP_Query;

class Posts extends Component
{
	/**
	 * @var array Arguments that'll be used for WP_Query
	 */
	public $post_args = [];
	/**
	 * @var int Total number of posts
	 */
	protected $number_of_posts;
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
		$this->setPosts();
		$this->setPagination();
	}

	/**
	 * Setter method for Pagination
	 */
	public function setPagination()
	{
		$defaults = [
			'query_param' => 'page',
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
		];

		$new_args = ArrayHelper::merge( $defaults, $this->post_args );

		$this->post_args = $new_args;

		$this->posts = new WP_Query($this->post_args);
	}
}