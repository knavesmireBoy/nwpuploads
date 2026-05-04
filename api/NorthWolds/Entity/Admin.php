<?php

namespace NorthWolds\Entity;

class Admin extends User
{

    public function __construct(...$args) {

        parent::__construct(...$args);

    }

    public function setRole(string $role)
    {
        if (!empty($this->roletable->find('id', $role))) {
            $this->userroletable->save(['userid' => $this->id, 'roleid' => $role]);
        }
    }
}
