<?php

namespace NorthWolds\Entity;

class Admin extends User
{

    public function __construct(...$args)
    {

        parent::__construct(...$args);
    }

    public function setRole(string $role, int $userid = 0)
    {
        if (!empty($this->roletable->find('id', $role))) {
            $this->userroletable->save(['userid' => $this->id, 'roleid' => $role]);
        }
    }

    public function delete($id, $subject)
    {

        dump('admin deleter...');
    }

    public function validateDelete()
    {
        return '_admin';
    }

    public function editPayload($id = '')
    {
        return [
            'calltext' => 'Delete User',
            'callroute' => "/user/delete/$id",
            'retour' => '_return2list.html.php'
        ];
    }

    public function loadPayload($selected = null)
    {
        $base =  [
            'callroute' => '/user/add/',
            'calltext' => 'Add New User',
            'retour' => '_return2uploads.html.php',
        ];
        if (!$selected) {
            return [
                ...$base,
                'optgroup' => 'clients'
            ];
        } else {
            return $base;
        }
    }

    public function getRoles(int $userid, string $roleid)
    {
        $f = composer(negate(curry2('equals')('Admin')), curry2('getter')('id'));
        $roleid = $this->getRole($userid);
        //list of roles determined by current user
        //allow Admin to be listed only if that happens to be one of the few Admin roles
        $cb = preg_match('/^Admin/', $roleid) ? 'identity' : $f;
        $roles = $this->fetchAllRoles($this->roles, [$roleid]);
        return safeFilter($roles, $cb ?? 'identity');
    }
}
