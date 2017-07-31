<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\widgets;


use rnd\base\Component;
use rnd\base\InvalidParamException;

class User extends Component
{
	/**
	 * @var int User ID
	 */
	protected $id;
	/**
	 * @var \WP_User
	 */
	protected $user;
	/**
	 * @var array User meta data
	 */
	protected $user_meta;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		if ($this->id === null) {
			throw new InvalidParamException('ID must be set in constructor!');
		}
		$this->user = get_userdata( $this->id);
		$this->user_meta = get_user_meta( $this->id);
	}

	/**
	 * User ID setter method
	 *
	 * @param int $value
	 */
	public function setId($value)
	{
		$this->id = $value;
	}

	/**
	 * Get user's display name
	 *
	 * @return string
	 */
	public function getDisplayName()
	{
		return $this->user->display_name;
	}

	/**
	 * Get user's full name
	 *
	 * @return string
	 */
	public function getFullName()
	{
		return $this->user_meta['first_name'][0] . ' ' . $this->user_meta['last_name'][0];
	}

	/**
	 * Get user nickname
	 *
	 * @return string
	 */
	public function getNickname()
	{
		return $this->user_meta['nickname'][0];
	}


}