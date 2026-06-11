<?php

namespace NorthWolds\Entity;

class Solo extends ClientAdmin
{

    public function preEdit($flag = true)
    {
        return $flag;
    }

    public function postEdit()
    {
        return $this->self ? 'success' : '';
    }

    public function delete($id, $details)
    {
        return 'last';
    }

    protected function validateRole($role)
    {
        $i = array_search($role, $this->roles);
        $j = array_search($this->getRole($this->id), $this->roles);
        if ($i < $j) { //demotion
            return $this->self ? 'lastadminrole' : '_lastadminrole';
        }
        return $role;
    }

    public function editPayload($id = '')
    {
        return [
            'calltext' => 'Add User',
            'callroute' => "/user/add/",
            'retour' => '_return2uploads.html.php'
        ];
    }

    public function presentList($userId)
    {
        return [[], []];
    }
}
