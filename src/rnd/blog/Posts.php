<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\blog;


use rnd\base\Component;
use rnd\helpers\ArrayHelper;
use rnd\helpers\StringHelper;
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
	 * Returns YOAST SEO title or default WP page/post title if former is not set.
	 *
	 * @param null|int $post_id Post ID
	 * @param bool|int $truncate If a number is given, the string will be truncated to that number of words
	 *
	 * @return mixed|string
	 */
	public static function yoastOrDefaultTitle( $post_id = null, $truncate = false )
	{
		$yoast_title = get_post_meta($post_id, '_yoast_wpseo_title', true);
		if ($yoast_title) {
			if ($truncate === false) {
				return $yoast_title;
			} else {
				return StringHelper::truncateWords( $yoast_title, $truncate);
			}
		}

		if ($truncate === false) {
			return get_the_title() . ' - ' . get_bloginfo('name');
		} else {
			return StringHelper::truncateWords( get_the_title(), $truncate);
		}
	}

	/**
	 * Returns Yoast SEO meta description or null if it's empty.
	 *
	 * @param null $post_id Post ID
	 *
	 * @return mixed|null
	 */
	public static function yoastOrDefaultDescription( $post_id = null )
	{
		$yoast_description = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true);
		if ($yoast_description) {
			return $yoast_description;
		}

		return null;
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