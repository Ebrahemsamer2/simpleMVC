<?php 

namespace App;

use App\Config;

class Mail
{
	public static function send($to, $subject, $text, $html)
	{
		require_once '../vendor/PHPMailer/PHPMailerAutoload.php';

		$fromEamil = "soltan_algaram41@yahoo.com";
	    $fromUsername = "Test";
	    
	    $sender_username = "soltan_algaram41@yahoo.com";
	    $sender_password = "000000gggh";
	    
	    $mail = new \PHPMailer;
	    $mail->CharSet = 'UTF-8';
	    $mail->Host = 'smtp.gmail.com';
	    $mail->Port = 993;
	    $mail->SMTPAuth = true;
	    $mail->SMTPSecure = 'tls';
	    $mail->Username = $sender_username;
	    $mail->Password = $sender_password;
	    $mail->setFrom($fromEamil, $fromUsername);
	    $mail->addAddress($to);
	    $mail->isHTML(true);
	    $mail->Subject = $subject;
	    $mail->Body = $html;
	    return $mail->send();
	}

}