<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\controllers;


use rnd\helpers\ArrayHelper;
use rnd\web\Controller;

class TestController extends Controller
{
	protected function setParamsTest()
	{
		$params = [
			'test' => 3,
			'test1' => 4
		];

		$this->sections['test']['params'] = ArrayHelper::merge( $this->sections['test']['params'], $params);
	}
}