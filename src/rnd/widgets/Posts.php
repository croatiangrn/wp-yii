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
	public $args = [];
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
		$this->setNumberOfPosts();
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
	 * Getter method for current page number
	 *
	 * @return int
	 */
	public function getCurrentPage()
	{
		return $this->current_page;
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

		$new_args = ArrayHelper::merge( $defaults, $this->args );

		$this->args = $new_args;

		$this->posts = new WP_Query($this->args);
	}

	public function getNumberOfPosts()
	{
		return $this->number_of_posts;
	}

	/**
	 * Setter method for number of posts
	 */
	protected function setNumberOfPosts()
	{
		$this->number_of_posts = $this->posts->found_posts;
	}
}