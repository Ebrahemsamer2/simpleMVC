<?php 

namespace App\Controllers;

use \Core\View;
use \App\Auth;

class Items extends Authenticated
{
	public function indexAction()
	{
		View::renderTemplate('Items/index.html');
	}
}