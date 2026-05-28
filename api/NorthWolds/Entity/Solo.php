<?php

namespace NorthWolds\Entity;

class Solo extends ClientAdmin
{

    public function __construct(...$args)
    {

        parent::__construct(...$args);
    }

    public function setRole(string $role, int $userid = 0)
    {
        if (!empty($this->roletable->find('id', $role))) {
            $this->userroletable->save(['userid' => $this->id, 'roleid' => $role]);
        }
    }


    public function delete($id)
    {
        dump('cannot delete clientAdmin');
    }

    public function validateDelete()
    {
        return 'last';
    }

    public function edit()
    {
        return true;
    }

    public function presentList($userId)
    {
        return [[], []];
    }
    public function getRoles(int $userid, string $roleid)
    {
        return [];
    }
}
