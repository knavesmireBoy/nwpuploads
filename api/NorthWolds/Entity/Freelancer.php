<?php

namespace NorthWolds\Entity;

class Freelancer extends User
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

    public function getUserIds($roles = null)
    {
        return [];
    }

    public function edit()
    {
        return true;
    }

    public function editPayload($id = '')
    {
        return [
            'calltext' => 'Delete User',
            'callroute' => "/user/delete/$id",
            'retour' => '_return2uploads.html.php'
        ];
    }

    public function presentList($userId)
    {
        return [[], []];
    }
}
