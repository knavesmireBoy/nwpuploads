<?php

namespace PoloAfrica\Controllers;

use \Ninja\DatabaseTable;

class Uploader
{
    public function __construct(private DatabaseTable $table, private DatabaseTable $usertable) {}

    public function getfiles(string $userid)
    {
        $user = $this->usertable->find('id', $userid);
        $user = $user[0] ?? null;
        dump($user->getDetails('email'));
    }
}
