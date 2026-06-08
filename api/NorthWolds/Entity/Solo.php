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

    public function delete($id)
    {
        return 'last';
    }

    protected function validateRole($role)
    {
        $i = array_search($role, $this->roles);

        dump([$this->roleid, $this->getRole($this->id)]);
        $j = array_search($this->roleid, $this->roles);

        dump([$i,$j]);
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
