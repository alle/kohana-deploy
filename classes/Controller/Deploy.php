<?php defined('SYSPATH') OR die('No direct script access.');

class Controller_Deploy extends Controller {

	public function action_deploy()
	{
		$deploy = Deploy::factory($this->request->param('token'));

		if ( ! $deploy->run())
		{
			$this->response->body(nl2br(implode(PHP_EOL, $deploy->errors())));

			return;
		}

		$this->response->body('Done.');
	}

}