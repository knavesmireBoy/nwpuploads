<?php

namespace NorthWolds\Entity;

class Solo extends User
{

    public function __construct(...$args)
    {

        parent::__construct(...$args);
    }

    public function delete($id)
    {
        return 'last';
    }

    protected function validateRole1($role) {
        return '_lastadminrole';
    }

    protected function validateRole($role)
    {
        $i = array_search($role, $this->roles);
        $j = array_search($this->roleid, $this->roles);
        if ($i < $j) { //demotion
            return $this->self ? 'lastadminrole' : '_lastadminrole';
        }
        return $role;
    }

    public function presentList($userId)
    {
        return [[], []];
    }
    public function getRoles(int $userid)
    {
        return [];
    }

    public function editPayload($id = '')
    {
        return [
            'calltext' => 'Add User',
            'callroute' => "/user/add/",
            'retour' => '_return2list.html.php'
        ];
    }
    //used if prompted
    public function loadPayload($id = '')
    {
        return [
            'retour' => '_return2uploads.html.php'
        ];
    }
}
