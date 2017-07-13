<?php

require( __DIR__ . '/BaseRnd.php');

class Rnd extends \rnd\BaseRnd {

}
//Rnd::$classMap = require(__DIR__ . '/../../vendor/autoload.php');
Rnd::$container = new rnd\di\Container();
