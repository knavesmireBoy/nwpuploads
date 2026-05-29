<?php

namespace NorthWolds\Entity;

class Freelancer extends User
{
    public function __construct(...$args)
    {
        parent::__construct(...$args);
    }

    public function getUserIds($roles = null)
    {
        return [];
    }

    public function edit($flag = true)
    {
        return $flag;
    }

    public function editPayload($id = '')
    {
        return [
            'calltext' => 'Delete User',
            'callroute' => "/user/delete/$id",
            'retour' => '_return2uploads.html.php'
        ];
    }
//used if prompted
    public function loadPayload($id = '')
    {
        return [
            'retour' => '_return2uploads.html.php'
        ];
    }

    public function presentList($userId)
    {
        return [[], []];
    }
}
