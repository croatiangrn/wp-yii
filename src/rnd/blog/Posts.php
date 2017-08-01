<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\blog;


use rnd\base\Component;
use rnd\helpers\ArrayHelper;
use WP_Query;

class Posts extends Component
{
	/**
	 * @var WP_Query
	 */
	protected $posts;


	/**
	 * Setter method for posts
	 *
	 * @param array $args Arguments for posts
	 */
	public function setPosts( $args = [])
	{
		$defaults = [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => get_option( 'posts_per_page' ),
		];
		$new_args = ArrayHelper::merge( $defaults, $args);

		$this->posts = new WP_Query($new_args);
	}

	/**
	 * Getter method for posts
	 *
	 * @return WP_Query
	 */
	public function getPosts()
	{
		return $this->posts;
	}

	/**
	 * Returns blog post thumbnail
	 *
	 * @param integer $post
	 * @param string $size
	 *
	 * @return string
	 */
	public function getPostThumbnail( $size = 'post-thumbnail', $post = null)
	{
		$post_thumbnail_id = get_post_thumbnail_id($post);
		if ( ! $post_thumbnail_id ) {
			return false;
		}
		return wp_get_attachment_image_url( $post_thumbnail_id, $size );
	}
}