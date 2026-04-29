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
        //slaterclark.co.uk
        $domains = [];
        $users = $this->usertable->findAll(null, 0, 0, \PDO::FETCH_ASSOC);
        $domain = $this->domain;
        $cb = function ($o) use ($domain) {
            dump($o);
            $e = $o['email'];
            $i = strrpos($e, '@');
            $dom = substr($e, $i + 1);
            return $dom === $domain && !$o['client_id'];
        };
        $domains = safeFilter($users, $cb);
        dump($domains);
    }
}
