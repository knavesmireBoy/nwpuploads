<?php

namespace NorthWolds\Controllers;

class Login
{
    private $authentication;

    public function __construct(\Ninja\Authentication $authentication)
    {
        $this->authentication = $authentication;
    }

    public function reg($key)
    {
        $lib = [112 => "jeff.tracy@tbsrgo.co.uk", 113 => "penny@fab1.co.uk"];
        $setcookie = doSetCookie(true);
        $unsetcookie = doSetCookie(false);

        if (isset($lib[$key])) {
            $email = $lib[$key];
            $unsetcookie('readit');
            $key = null;
        } else if ($key && $key == 'readit') {
            $setcookie('readit');
            $key = null;
            // reLocate("/logger/reg/$key");
        }

        $user = $this->authentication->isLoggedIn();
        if (!$user) {
            return [
                'template' => 'login.html.php',
                'title' => 'Admin',
                'variables' => [
                    'action' => '/logger/login/',
                    'email' => $email ?? null,
                    'loginerror' => $key ? 'Unable to login, please check password and email address:' : ''
                ]
            ];
        } else {
            reLocate("/uploader/load");
        }
    }

    public function login(...$args)
    {
        $user = $this->authentication->isLoggedIn();
        if (!$user) {
            return $this->reg(...$args);
        } else {
            // retour();
        }
    }

    public function logout()
    {
        $this->authentication->logout();
        reLocate("/uploader/load/");
    }

    public function loginSubmit()
    {
        $success = $this->authentication->login($_POST['email'], $_POST['password']);
        if ($success) {
            reLocate("/uploader/load/");
            exit();
        } else {
            return $this->login('login', 'Unable to login, please check password and email address:');
        }
    }
}
