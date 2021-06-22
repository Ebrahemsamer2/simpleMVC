<?php 

namespace App\Controllers;

use \Core\View;
use \App\Auth;
use \App\Flash;
use \App\Models\User;

class Profile extends Authenticated
{

	protected function before()
	{
		parent::before();
		$this->user = Auth::getUser();
	}

	public function showAction()
	{
		View::renderTemplate("Profile/show.html", [
			'user' => $this->user
		]);
	}

	public function editAction()
	{
		View::renderTemplate("Profile/edit.html", [
			'user' => $this->user
		]);
	}

	public function updateAction()
	{
		if($this->user->updateUserProfile($_POST))
		{
			Flash::addMessage("Profile has updated successfully");
			$this->redirecT("/profile/show");
		}
		else 
		{
			View::renderTemplate("Profile/edit.html", [
				'user' => $this->user
			]);
		}
	}

}