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

    /*sync check if creating client AFTER creating an "employee" assign the newly created client_id to any "employees"
    could prompt a dialog or just do it
    */

    public function associate($id)
    {
        $this->usertable->save(['id' => $id, 'client_id' => $this->id]);
    }

    public function checkUserDomains()
    {
        $users = $this->usertable->findAll();
        $domain = $this->domain;
        $cb = function ($o) use ($domain) {
            $e = $o->email;
            $i = strrpos($e, '@');
            $dom = substr($e, $i + 1);
            return !$o->client_id && $dom === $domain;
        };
        $domains = safeFilter($users, $cb);
        dump($domains);
        return $domains;

        foreach ($domains as $user) {
            $this->usertable->save(['id' => $user->id, 'client_id' => $this->id]);
        }
    }
}
