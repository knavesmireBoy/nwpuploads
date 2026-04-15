<?php

namespace PoloAfrica\Controllers;

//include_once __DIR__ . '/../config.php';
//include_once FUNCTIONS;

class Bolt
{
    public function __construct(private \Ninja\Authentication $authentication)
    {
        $this->authentication = $authentication;
    }
    public function shout()
    {
        return [
            'template' => 'actions.html.php',
            'title' => 'Log In Successful',
            'variables' => [
                'user' => 'bolty',
                'color' => 'spotty'
            ]
        ];
    }
    public function fartSubmit()
    {
        $success = $this->authentication->login($_POST['email'], $_POST['password']);
        dump($success);
        if ($success) {
            reLocate("/uploader/load/");
            exit();
        }
        reLocate(BBC);
    }
}
