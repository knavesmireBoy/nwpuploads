<?php

namespace NorthWolds\Users;



class Admin extends Useroo
{
    public function add()
    {
        dump('Sure I can add, ha!');
    }

    public function edit($id = 0, $args = [])
    {
        dump('Sure I can add, ha!');
    }

    public function delete()
    {
        dump('admin and can delete but...');
    }

    public function setSubject(Useroo $subject){

    }
}
