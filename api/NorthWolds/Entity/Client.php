<?php

namespace NorthWolds\Entity;

class Client extends Entity
{
    public $id;
    public $name;
    public $domain;
    public $tel;

    public function __construct(private \Ninja\DatabaseTable $table, private \Ninja\DatabaseTable $usertable) {}

    public function getDetails($arg)
    {
        if (is_numeric($arg)) {
            return $this->fetch('TABLE', 'id', $arg);
        } else {
            return $this->fetch('TABLE', 'domain', $arg, 'name');
        }
    }

    public function foo()
    {
        
        
        $users = $this->usertable->findAll(null, 0, 0, \PDO::FETCH_ASSOC)[0];

            dump($users);
        $cb = function($o) {
            $i = strrpos($o->email, '@');
            return substr($o->email, $i + 1);
        };

        $d = array_map($cb, $users);
    }
}
