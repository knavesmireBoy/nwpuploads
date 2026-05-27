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

    public function editPayload($id = '')
    {
        return [
            'calltext' => 'Delete User',
            'callroute' => "/user/delete/$id",
            'retour' => '_return2list.html.php'
        ];
    }

    public function loadPayload()
    {
        return [
            'callroute' => '/user/add/',
            'calltext' => 'Add New User',
            'retour' => '_return2uploads.html.php'
        ];
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
