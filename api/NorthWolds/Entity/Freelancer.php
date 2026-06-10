<?php

namespace NorthWolds\Entity;

class Freelancer extends User
{

    public function editPayload($id = '')
    {
        return [
            'calltext' => 'Delete User',
            'callroute' => "/user/delete/$id",
            'retour' => '_return2uploads.html.php'
        ];
    }


    public function getUserIds($roles = null)
    {
        return [];
    }
}
