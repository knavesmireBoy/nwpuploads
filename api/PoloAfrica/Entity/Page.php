<?php

namespace PoloAfrica\Entity;

class Page
{
    public $id;
    public $name;
    public $title;
    public $content;
    public $description;
    private $table;

    public function __construct(\Ninja\DatabaseTable $table)
    {
    }
  
    public function findAll(...$args)
    {
        return [];
        return $this->table->findAll(...$args);
    }

    public function find($id)
    {
        $ret = $this->table->find('id', $id, null, 0, 0, \PDO::FETCH_ASSOC);
        return $ret ? $ret[0] : $ret;
    }

    public function setName($name)
    {
       // return $this->table->setName($name);
    }

    public function getSlotEntity()
    {
        return $this->table->getEntity();
    }

    public function repop($data = [], $flag = false)
    {
        $slot = $this->getSlotEntity();
        return $slot->repop($data, $flag);
    }

    public function swap(...$args)
    {
        $slot = $this->getSlotEntity();
        return $slot->swap(...$args);
    }

    public function shuffle(...$args)
    {
        $slot = $this->getSlotEntity();
        return $slot->shuffle(...$args);
    }
}
