<?php

namespace PoloAfrica\Controllers;

class Spadger
{
    public function __construct()
    {
    }
    public function shout()
    {
        return [
            'template' => 'actions.html.php',
            'title' => 'Log In Successful',
            'variables' => [
                'user' => 'spadger',
                'color' => 'tan'
            ]
        ];
    }

    public function fartSubmit()
    {
        reLocate(DEZ);
    }
}