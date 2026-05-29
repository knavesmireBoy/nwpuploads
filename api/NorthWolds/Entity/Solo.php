<?php

namespace NorthWolds\Entity;

class Solo extends ClientAdmin
{

    public function __construct(...$args)
    {

        parent::__construct(...$args);
    }


    public function delete($id)
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

}
