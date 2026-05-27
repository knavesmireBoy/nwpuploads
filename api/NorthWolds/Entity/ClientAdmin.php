<?php

namespace NorthWolds\Entity;

class ClientAdmin extends User
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
    public function delete($id)
    {
        dump('me a deleter...');
    }

    public function getUserIds($roles = null)
    {
        $users = $this->table->find('client_id', $this->client_id);
        $res = array_map(fn($o) => $o->id, $users);
        if (is_bool($roles)) {
            $ret = $this->getAllRoles($res);
            return $roles ? $ret : $this->getAdminRoles($ret);
        }
        return $res;
    }

    public function validateDelete()
    {
        $msg = '';
        $ids = $this->getUserIds();
       
        $roles = $this->getAllRoles($ids);
        $adminroles = $this->getAdminRoles($roles);
        if (count($adminroles) === 1) {
            $ids = array_column($adminroles, 'userid');
            $key = in_array($this->id, $ids) ? 'lasteditor' : '';
            $msg = $key;
        }
        return $msg;
    }
}
