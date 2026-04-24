<?php

namespace NorthWolds\Entity;

class Client extends Entity
{
    private $table;
    public $id;
    public $name;
    public $domain;
    public $tel;

    public function __construct(\Ninja\DatabaseTable $table)
    {
        $this->table = $table;
    }

    public function getDetails($arg)
    {
        if (is_numeric($arg)) {
            return $this->fetch('TABLE','id', $arg);
        } else {
            return $this->fetch('TABLE', 'domain', $arg);
        }
    }
}
