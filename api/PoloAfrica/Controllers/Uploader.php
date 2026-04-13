<?php

namespace PoloAfrica\Controllers;

use \Ninja\DatabaseTable;

class Uploader
{
    public function __construct(private DatabaseTable $table, private $userid) {}

    public function git()
    {
        dump($this->userid);
    }
}
