<?php
/**
 * @author: Marko Mikulic <mikulic.marko@spark.ba>
 */

namespace rnd\base;

class User extends Component
{
    protected $meta_prefix = "user_";
    /**
     * @var int
     */
    public $user_id;
    /**
     * @inheritdoc
     */
    public function init() {
        $this->setUserId();
    }

    /**
     * User ID setter
     *
     * @param null|int $user_id
     * @return User
     */
    public function setUserId($user_id = null) {
        if ($user_id === null) {
            $this->user_id = get_current_user_id();
        } else {
            $this->user_id = $user_id;
        }

        return $this;
    }
    /**
     * User ID getter
     *
     * @return int
     */
    public function getUserId() {
        return $this->user_id;
    }

    /**
     * Returns info about user's meta including meta_prefix
     *
     * @see $meta_prefix
     *
     * @param $meta_key
     * @param $single bool Whether to return data as array or string|int
     * @param bool $skip_prefix Whether to skip prefix
     * @return mixed
     */
    public function getInfo($meta_key, $single = true, $skip_prefix = false) {
        if ($skip_prefix) {
            return get_user_meta($this->user_id, $meta_key, $single);
        }
        return get_user_meta($this->user_id, $this->meta_prefix . $meta_key, $single);
    }

    /**
     * This method checks if user is logged in or not
     *
     * @return bool
     */
    public function isGuest() {
        return !is_user_logged_in();
    }

    /**
     * This method checks whether current user is admin or not
     *
     * @return bool
     */
    public function isAdmin() {
        return current_user_can('administrator');
    }

    /**
     * Returns users full name (concatenation of First name and Last name)
     *
     * @return string
     */
    public function getFullName() {
        return $this->getInfo('first_name', true, true) . ' ' . $this->getInfo('last_name', true, true);
    }

    /**
     * Returns users e-mail, if user ID is invalid, returns null
     *
     * @return null|string
     */
    public function getEmail() {
        /** @var false|\WP_User $user_data */
        $user_data = get_userdata($this->user_id);
        if ($user_data) {
            return $user_data->user_email;
        }
        return null;
    }
}