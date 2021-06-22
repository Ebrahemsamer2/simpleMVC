<?php 


namespace App;

class Flash
{
	CONST SUCCESS = 'success';
	CONST WARNING = 'warning';
	CONST INFO = 'info';
	CONST DANGER = 'danger';

	public static function addMessage($message, $type = 'success')
	{
		if(! isset($_SESSION['flash_notifications']))
		{
			$_SESSION['flash_notifications'] = [];
		}
		$_SESSION['flash_notifications'][] = ['message' => $message, 'type' => $type];
	}

	public static function getMessages()
	{
		$messages = isset($_SESSION['flash_notifications']) ? $_SESSION['flash_notifications'] : [];
		unset($_SESSION['flash_notifications']);
		return $messages;
	}
}