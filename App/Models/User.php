<?php

namespace App\Models;

use PDO;
use \App\Mail;
use \Core\View;

class User extends \Core\Model
{

    public $errors = [];

    public function __construct($data = [])
    {
        foreach($data as $key => $value)
        {
            $this->$key = $value;
        }
    }

    public function save()
    {
        $this->validate();

        if(empty($this->errors))
        {
            $token = new \App\Token();
            $hashed_token = $token->getHash();
            $this->activation_token = $token->getToken();

            $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);

            $query = "INSERT INTO users  (username, email, password,activation_token_hash) VALUES (:username, :email, :password, :activation_token_hash)";

            $db = static::getDB();
            $stmt = $db->prepare($query);

            $stmt->bindValue(":username", $this->username, PDO::PARAM_STR);
            $stmt->bindValue(":email", $this->email, PDO::PARAM_STR);
            $stmt->bindValue(":password", $hashed_password, PDO::PARAM_STR);
            $stmt->bindValue(":activation_token_hash", $hashed_token, PDO::PARAM_STR);

            return $stmt->execute();
        }
        return false;
    }

    public function validate()
    {
        if($this->username == '')
        {
            $this->errors[] = 'Empty username';
        }

        if(filter_var($this->email, FILTER_VALIDATE_EMAIL) == false)
        {
            $this->errors[] = 'Invalid email';
        }

        if(self::emailExists($this->email, $this->id ?? null))
        {
            $this->errors[] = 'Email is already exists.';
        }

        if(isset($this->password))
        {
            if(strlen($this->password) < 6)
            {
                $this->errors[] = 'Password is too short.';
            }

            if(preg_match('/.*[a-z]+.*/i', $this->password) == false)
            {
                $this->errors[] = 'Password should have at least one letter';
            }
        }
    }

    public static function emailExists($email, $ignore_id = null)
    {
        $user = self::findByEmail($email);
        if($user)
        {
            if($user->id != $ignore_id)
            {
                return true;
            }
        }
        return false;
    }
    
    public static function findByEmail($email)
    {
        $query = "SELECT * FROM users WHERE email = :email";
        $db = static::getDB();

        $stmt = $db->prepare($query);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);

        $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

        $stmt->execute();

        return $stmt->fetch();
    }

    public static function findById($id)
    {
        $query = "SELECT * FROM users WHERE id = :id";
        $db = static::getDB();

        $stmt = $db->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->setFetchMode( PDO::FETCH_CLASS, get_called_class() );
        $stmt->execute();

        return $stmt->fetch();
    }

    public static function authenticate($email, $password)
    {
        $user = self::findByEmail($email);
        if($user && $user->is_active)
        {
            if(password_verify($password, $user->password))
                return $user;
        }
        return false;
    }

    public static function sendPasswordReset($email)
    {
        $user = self::findByEmail($email);

        if($user)
        {
            if($user->startPasswordReset())
            {
                $user->sendPasswordResetEmail();
            }
        }
    }

    protected function startPasswordReset()
    {
        $token = new \App\Token();
        $hash = $token->getHash();
        $this->password_reset_token = $token->getToken();

        $expiry_timestamp = time() + 60 * 60 * 2; // 2 hours
        $query = "UPDATE users SET password_reset_hash = :password_reset_hash, password_reset_expiry_at = :password_reset_expiry_at WHERE id = :id";

        $db = static::getDB();
        $stmt = $db->prepare($query);

        $stmt->bindValue(':password_reset_hash', $hash, PDO::PARAM_STR);
        $stmt->bindValue(':password_reset_expiry_at', date('Y-m-d H:i:s', $expiry_timestamp), PDO::PARAM_STR);
        $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    protected function sendPasswordResetEmail()
    {
        $url = 'http://' . $_SERVER['HTTP_HOST'] . '/password/reset/' . $this->password_reset_token;

        $text = View::getTemplate('Password/reset_email.txt', ['url' => $url]);
        $html = View::getTemplate('Password/reset_email.html', ['url' => $url]);

        echo $url; exit;

        Mail::send($this->email, "test subject", $text, $html);
    }

    public static function findByPasswordReset($passwordToken)
    {
        $token = new \App\Token($passwordToken);
        $hash = $token->getHash();

        $query = "SELECT * FROM users WHERE password_reset_hash = :password_reset_hash";

        $db = static::getDB();
        $stmt = $db->prepare($query);
        $stmt->bindValue(":password_reset_hash", $hash, PDO::PARAM_STR);

        $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
        $stmt->execute();

        $user = $stmt->fetch();

        if($user)
        {
            if(strtotime($user->password_reset_expiry_at) > time())
            {
                return $user;
            }
        }
    }

    public function resetPassword($password)
    {
        $this->password = $password;
        $this->validate();
        if( empty($this->errors) )
        {
            $password = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET password = :password, password_reset_hash = NULL, password_reset_expiry_at = NULL WHERE id = :id";

            $db = static::getDB();
            $stmt = $db->prepare($query);

            $stmt->bindValue(":password", $password, PDO::PARAM_STR);
            $stmt->bindValue(":id", $this->id, PDO::PARAM_INT);

            return $stmt->execute();

        }
        return false;
    }

    public function sendActivationEmail()
    {
        $url = 'http://' . $_SERVER['HTTP_HOST'] . '/signup/activate/' . $this->activation_token;

        $text = View::getTemplate('Signup/activation_email.txt', ['url' => $url]);
        $html = View::getTemplate('Signup/activation_email.html', ['url' => $url]);

        echo $url; exit;

        Mail::send($this->email, "Account Activation", $text, $html);
    }

    public static function activate($token)
    {
        $token = new \App\Token($token);
        $hash = $token->getHash();

        $query = "UPDATE users SET is_active = 1 , activation_token_hash = null WHERE activation_token_hash = :activation_token_hash";

        $db = static::getDB();
        $stmt = $db->prepare($query);
        $stmt->bindValue(":activation_token_hash", $hash, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function updateUserProfile($data)
    {
        $this->username = $data['username'];
        $this->email = $data['email'];
        if($data['password'] != '')
        {
            $this->password = $data['password'];
        }
        
        $this->validate();

        if(empty($this->errors))
        {
            $query = "UPDATE users SET username = :username, email = :email";
            if($data['password'] != '')
            {
                $query .= ", password = :password";
            }
            $query .= " WHERE id = :id";

            $db = static::getDB();
            $stmt = $db->prepare($query);
            $stmt->bindValue(":username", $this->username, PDO::PARAM_STR);
            $stmt->bindValue(":email", $this->email, PDO::PARAM_STR);
            if($data['password'] != '')
            {
                $stmt->bindValue(":password", password_hash( $this->password, PASSWORD_DEFAULT), PDO::PARAM_STR);
            }

            $stmt->bindValue(":id", $this->id, PDO::PARAM_INT);
            return $stmt->execute();
        }
        return false;
    }
}