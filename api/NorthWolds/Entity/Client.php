<?php

namespace NorthWolds\Entity;

class Client extends Entity
{
    public $id;
    public $name;
    public $domain;
    public $tel;
    protected $table;
    protected $usertable;

    public function __construct(\Ninja\DatabaseTable $table, \Ninja\DatabaseTable $usertable) {

        $this->table = $table;
        $this->usertable = $usertable;
    }

    public function getDetails($arg)
    {
        if (is_numeric($arg)) {
            return $this->fetch('TABLE', 'id', $arg);
        } else {
            return $this->fetch('TABLE', 'domain', $arg, 'name');
        }
    }

    public function getUsers() {}

    public function domainAvailable($domain)
    {
        $all = $this->table->findAll();
        list($edom) = parseEmail($domain);
        $edoms = array_map(function ($item) {
            list($edom) = parseEmail($item->domain);
            return $edom;
        }, $all);
        return safeFilter($edoms, fn($edomain) => $edomain === $edom) === [];
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

    public function validateDomain($email, $prop = 'id')
    {
        list($dom, $com) = parseEmail($email);
        return $this->domain === "$dom.$com" ? [$prop => $this->id] : [$prop => null];
    }
}
