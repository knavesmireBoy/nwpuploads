<?php

namespace PoloAfrica\Controllers;

class Bolt
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
                'user' => 'bolty',
                'color' => 'spotty'
            ]
        ];
    }
}