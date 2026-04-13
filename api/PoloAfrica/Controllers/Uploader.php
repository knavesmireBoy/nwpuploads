<?php

namespace PoloAfrica\Controllers;

use \Ninja\DatabaseTable;

class Uploader
{
    public function __construct(private DatabaseTable $table) {}

    public function git(string $userid)
    {
        dump($userid);
    }
}
