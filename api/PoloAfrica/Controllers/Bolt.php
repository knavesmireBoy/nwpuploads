<?php
include_once __DIR__ . '/../config.php';
include_once FUNCTIONS;
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
    public function fartSubmit(){
        reLocate(BBC);
    }
}