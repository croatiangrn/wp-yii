<?php
/**
 * @author: Marko Mikulic <mikulic.marko@spark.ba>
 */

namespace rnd\web;


use Rnd;
use rnd\base\Component;
use rnd\base\InvalidConfigException;

class User extends Component
{
    /**
     * @var string the class name of the [[identity]] object.
     */
    public $identityClass;

    /**
     * Initializes the application component.
     */
    public function init()
    {
        parent::init();

        if ($this->identityClass === null) {
            throw new InvalidConfigException('User::identityClass must be set.');
        }

        if (!empty($this->accessChecker) && is_string($this->accessChecker)) {
            $this->accessChecker = Rnd::createObject($this->accessChecker);
        }
    }
}