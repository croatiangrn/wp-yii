<?php
declare(strict_types=1);

require( __DIR__ . '/BaseRnd.php');

class Rnd extends \rnd\BaseRnd {

}
//Rnd::$classMap = require(__DIR__ . '/../../vendor/autoload.php');
Rnd::$container = new rnd\di\Container();

if (!function_exists('dd')) {
	function dd()
	{
		$args = func_get_args();
		echo '<pre>';
		call_user_func_array('var_dump', $args);
        echo '</pre>';
		die();
	}
}