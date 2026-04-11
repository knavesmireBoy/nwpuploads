<?php

namespace PoloAfrica\Controllers;

class Login
{
    private $authentication;
    public function __construct(/*\Ninja\Authentication*/ $authentication)
    {
        $this->authentication = $authentication;
    }

    public function reg($arg = '')
    {
       // $user = $this->authentication->isLoggedIn();
       $user = null;
        if (!$user) {
            return [
                'template' => 'login.html.php',
                'title' => 'Admin',
                'variables' => []
            ];
        } else {
           // reLocate(BADMINTON, '../'); //
        }
    }

    public function login($errors = [], $msg = '')
    {
        /* there is nothing to prevent people guessing a path to logging in
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
      //  reLocate(BADMINTON, '../'); //
    }

    public function shout()
    {
        return [
            'template' => 'actions.html.php',
            'title' => 'Log In Successful',
            'variables' => [
                'user' => 'LOGIN'
            ]
        ];
    }

    public function loginSubmit()
    {
        if (!empty($_POST)) {
            $user = $_POST['user'];
            $success = $this->authentication->login($user['email'], $user['password']);
            $user = $this->authentication->isLoggedIn();

            if ($success) {
                //chiefly to set $_SESSION['filestore'], but also for ajax
              //  reLocate(BADMINTON . "/success");

                return [
                    'template' => 'actions.html.php',
                    'title' => 'Log In Successful',
                    'variables' => [
                        'userid' => $user->id ?? '',
                        'admin' => $user->hasPermission(\PoloAfrica\Entity\User::ACCOUNT_EDITOR),
                        'username' => "you are logged in as $user->name",
                        'user' => $user
                    ]
                ];
            } else {
                return $this->login(['Login Failed'], 'Unable to login, please check password and email address:');
            }
        } else {
            retour();
        }
    }
}
