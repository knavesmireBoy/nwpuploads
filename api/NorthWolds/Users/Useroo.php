<?php

namespace NorthWolds\Users;

class Useroo
{
    private $subject;
    public function __construct(private string $home) {}
    public function add()
    {
        dump('no can do');
    }

    public function edit($id = 0, $args = [])
    {
        $vars = [
            'action' => '/user/load/read',
            'class' => 'details override',
            'message' => 'You may view this users details but cannot edit'
        ];
    }

    public function delete()
    {
        dump('cannot delete');
    }
}
