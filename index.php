<?php

use rnd\widgets\Menu;

echo Menu::widget([
	'items' => [
		// Important: you need to specify url as 'controller/action',
		// not just as 'controller' even if default action is used.
		['label' => 'Home', 'url' => get_home_url()],
		// 'Products' menu item will be selected as long as the route is 'product/index'
		['label' => 'Products', 'url' => get_home_url(), 'items' => [
			['label' => 'New Arrivals', 'url' => get_home_url()],
			['label' => 'Most Popular', 'url' => get_home_url()],
		]],
	],
]);