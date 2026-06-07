<?php

namespace NorthWolds\Entity;

class Entity implements \SplSubject
{

    protected \SplObjectStorage $observers;

    protected array $users = [];
    protected static $instance;

    protected $roleid; //NOTE ::getDetails returns a role field BUT property is $roleid
    protected $table;
    protected $roletable;
    protected $userroletable;
    protected $clienttable;

    public function __construct(\Ninja\DatabaseTable $table, \Ninja\DatabaseTable $client, \Ninja\DatabaseTable $userrole, \Ninja\DatabaseTable $role)
    {
        $this->table = $table;
        $this->userroletable = $userrole;
        $this->roletable = $role;
        $this->clienttable = $client;
        $this->observers = new \SplObjectStorage;
    }

    private function __clone() {}
    public function createUser(User $user): void
    {
        $this->users[] = $user;
        $this->notify();
    }
    public function attach(\SplObserver $observer): void
    {
        $this->observers->attach($observer);
    }
    public function detach(\SplObserver $observer): void
    {
        $this->observers->detach($observer);
    }
    public function notify(): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
    }

    protected function fetch($t, $prop, $val, ...$rest)
    {
        $ret = [];
        if ($val) { //safeguard against missing values
            if (strtoupper($t) === $t) {
                $t = strtolower($t);
                $ret = $this->{$t}->find($prop, $val, null, 0, 0, \PDO::FETCH_ASSOC);
            } else {
                $ret = $this->{$t}->find($prop, $val, ...$rest);
            }
        }
        return empty($ret) ? null : $ret[0];
    }


    protected function setCookie(array $data, array $mandatory, mixed $flag)
    {
        $setcookie = doSetCookie($flag);
        foreach ($mandatory as $prop) {
            $arg = isset($data[$prop]) && $flag ? $data[$prop] : '';
            $setcookie($prop, $arg);
        }
    }

    public function find(...$args)
    {
        return $this->fetch(...$args);
    }
}
