<?php

namespace NorthWolds\Controllers;

class Login
{
    private $authentication;

    public function __construct(\Ninja\Authentication $authentication)
    {
        $this->authentication = $authentication;
    }

    public function reg($arg = '')
    {
        $user = $this->authentication->isLoggedIn();
        if (!$user) {
            return [
                'template' => 'login.html.php',
                'title' => 'Admin',
                'variables' => [
                    'action' => '/logger/login/'
                ]
            ];
        } else {
            // reLocate(BADMINTON, '../'); //
        }
    }

    public function login($errors = [], $msg = '')
    {
        /*
        there is nothing to prevent people guessing a path to logging in
        logger/login/3/5/6 is_numeric check would at least suppress that kind of malarkey
        */
        $user = $this->authentication->isLoggedIn();
        if (!$user) {
            return $this->reg();
        } else {
            retour();
        }
    }

    public function login1($errors = [], $msg = '')
    {
        /*
        there is nothing to prevent people guessing a path to logging in
        logger/login/3/5/6 is_numeric check would at least suppress that kind of malarkey
        */
        $user = $this->authentication->isLoggedIn();
        if (!$user) {
            return [
                'template' => 'register.html.php',
                'title' => 'Admin',
                'variables' => [
                    'errors' => is_numeric($errors) ? [] : $errors,
                    'route' => 'login',
                    'submit' => 'Log In',
                    'action' => LOGIN,
                    'userid' => '',
                    'owner' => '',
                    'msg' => is_numeric($msg) ? '' : $msg
                ]
            ];
        } else {
            retour();
        }
    }

    public function logout()
    {
        $this->authentication->logout();
    }

    public function loginSubmit()
    {
        $success = $this->authentication->login($_POST['email'], $_POST['password']);
        if ($success) {
            reLocate("/uploader/load/");
            exit();
        } else {
            return $this->login(['Login Failed'], 'Unable to login, please check password and email address:');
        }
    }
}
