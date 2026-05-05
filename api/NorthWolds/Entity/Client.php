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

    public function getUsers() {}

    public function validateDomain($domain)
    {
        $all = $this->table->findAll();
        list($dom, $com) = parseEmail($domain);
        $doms = array_map(fn($item) => parseEmail($item->domain), $all);
        dump($doms);
    }

    //sync check if creating client AFTER creating an "employee" assign the newly created client_id to any "employees"
    public function associate(int $id)
    {
        $this->usertable->save(['id' => $id, 'client_id' => $this->id]);
    }

    public function syncWithUsers($flag = false)
    {
        $users = $this->usertable->findAll();
        $domain = $this->domain;
        $cb = function ($o) use ($domain) {
            $e = $o->email;
            $i = strrpos($e, '@');
            $dom = substr($e, $i + 1);
            return !$o->client_id && $dom === $domain;
        };
        $users = safeFilter($users, $cb);
        if ($flag) {
            foreach ($users as $user) {
                $this->associate($user->id);
            }
        } else {
            return $users;
        }
    }
}
