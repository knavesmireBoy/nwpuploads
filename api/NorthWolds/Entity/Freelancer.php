<?php

namespace NorthWolds\Entity;

class Freelancer extends User
{
    
    public function presentList($userId)
    {
        return [[], []];
    }

    public function postEdit()
    {
      return $this->self ? 'success' : '';
    }
    
    public function getUserIds($roles = null)
    {
        return [];
    }

    public function editPayload($id = '')
    {
        return [
            'calltext' => 'Delete User',
            'callroute' => "/user/delete/$id",
            'retour' => '_return2uploads.html.php'
        ];
    }

 
}
