<?php

namespace App\Controllers;

use \Core\View;

/**
 * Home controller
 *
 * PHP version 7.0
 */
class Home extends \Core\Controller
{

    /**
     * Show the index page
     *
     * @return void
     */
    public function indexAction()
    {
        // \App\Mail::send('ebrahemsamer2@gmail.com', 'rerere', 'fdfdfd', '<h2>fdsfds</h2>');
        View::renderTemplate('Home/index.html');
    }
}
