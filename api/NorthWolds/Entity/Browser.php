<?php

namespace NorthWolds\Entity;

class Browser extends Employee
{

    public function __construct(...$args) {

        parent::__construct(...$args);

    }

    public function setRole(string $role, int $userid = 0)
    {
        if (!empty($this->roletable->find('id', $role))) {
            $this->userroletable->save(['userid' => $this->id, 'roleid' => $role]);
        }
    }

    public function delete($id){
        return '';
    }

    public function presentList($userId){
        return [[], []];
}
}
