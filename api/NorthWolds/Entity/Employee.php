<?php

namespace NorthWolds\Entity;

class Employee extends ClientAdmin
{
    public function __construct(...$args)
    {
        parent::__construct(...$args);
    }

    protected function resetClient($cid)
    {
        $ret = $this->fetch('clienttable', 'id', $cid);
        return [$cid, $ret->domain];
    }

    public function presentList($userId)
    {
        return [[], []];
    }

    public function getRoles(int $userid)
    {
        return [];
    }

    public function edit($flag = true)
    {
        return $flag;
    }
}
