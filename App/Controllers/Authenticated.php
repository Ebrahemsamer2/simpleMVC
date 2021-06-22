<?php 

namespace App\Controllers;

class Authenticated extends \Core\Controller 
{
	// Exists in Controller, we override it.
	protected function before()
	{
		$this->requireLogin();
	}
}