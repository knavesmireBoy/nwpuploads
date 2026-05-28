<?php

namespace NorthWolds\Entity;

class Employee extends ClientAdmin
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

    public function edit()
    {
        return true;
    }

    public function editPayload($id = '')
    {
        if (!$this->self) {
            return [
                'action' => '/user/load/read',
                'class' => 'details override',
                'message' => 'You may view this users details but cannot edit'
            ];
        } else {
            return [
                'calltext' => 'Delete User',
                'callroute' => "/user/delete/$id",
                'retour' => '_return2uploads.html.php'
            ];
        }
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
