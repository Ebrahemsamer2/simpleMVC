<?php 

namespace App\Controllers;

use App\Models\User;
use App\Auth;
use Core\View;
use App\Flash;

class Login extends \Core\Controller 
{
	public function newAction()
	{
		View::renderTemplate('Login/new.html');
	}

	public function createAction()
	{
		$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
		$password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
		$user = User::authenticate($email, $password);
		if($user)
		{
			Auth::login($user);
			Flash::addMessage("Logged in Successfully");
			$this->redirect(Auth::redirectToRememberedPage());
		}
		else
		{
			View::renderTemplate('Login/new.html', [
				'email' => $email
			]);
		}
	}

	public function destroyAction()
	{
		Auth::logout();
		$this->redirect('/login/show-message-at-logout');
	}

	public function showMessageAtLogout()
	{
		Flash::addMessage("Logout Successfully");
		$this->redirect("/");
	}
}