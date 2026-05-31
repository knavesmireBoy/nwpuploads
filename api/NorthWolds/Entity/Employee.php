<?php

namespace NorthWolds\Entity;

class Employee extends ClientAdmin
{
    public function __construct(...$args)
    {
        parent::__construct(...$args);
    }

    public function edit($flag = true)
    {
      return $flag;
    }
    
    public function editPayload($id = '')
    {
        return [
            'retour' => '_return2uploads.html.php'
        ];
    }

    public function presentList($userId)
    {
        return [[], []];
    }

    public function getRoles(int $userid)
    {
        return [];
    }
}
