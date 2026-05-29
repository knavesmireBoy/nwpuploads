<?php

namespace NorthWolds\Entity;

class Browser extends Employee
{

    public function __construct(...$args)
    {

        parent::__construct(...$args);
    }

    public function presentList($userId)
    {
        return [[], []];
    }
    public function getRoles(int $userid)
    {
        return [];
    }
}
