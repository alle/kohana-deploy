<?php defined('SYSPATH') OR die('No direct script access.');

Route::set('kohana-deploy', 'deploy(-<token>)')
	->defaults(array(
		'controller' => 'deploy',
		'action'     => 'deploy'
	));