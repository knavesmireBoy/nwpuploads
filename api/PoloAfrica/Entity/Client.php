<?php

namespace NorthWolds\Entity;

class Client
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

    public function getDetails($id) {

        return $this->table->find('id', $id);

    }
}
