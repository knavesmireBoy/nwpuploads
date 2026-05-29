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
      
      
      dump(999);
        $uid = $userid ? $userid : $this->id;
      //if $action is true insert otherwise update
      $action = empty($this->userroletable->find('userid', $uid));
      //A) if validation fails return a key for query eg 'last' header(Location: /user/load/last)
      $role = $this->validateRole($role);
  
      if (in_array($role, $this->roles)) {
        $this->userroletable->save(['userid' => $this->id, 'roleid' => $role], $action);
        $this->roleid = $userid ? null : $role;
        //B if validation succeeds set to empty string
        $role = '';
      }
      return $role;
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

    public function delete($id)
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
            'selected' => true,//!!foregoes the drop down menu required by Admin
            'retour' => '_return2uploads.html.php'
        ];
    }

    public function getRoles(int $userid)
    {
      $f = composer(negate(curry2('equals')('Admin')), curry2('getter')('id'));
      $roleid = $this->getRole($userid);
      $roles = $this->fetchAllRoles($this->roles, [$roleid]);
      return safeFilter($roles, $f);
    }

    public function presentList($userId){
        $user = $this->table->find('id', $userId);
        $user = $user[0] ?? null;
        if (isset($user)) {
            $users = $user->getUserIds();
            if (isset($users[1])) {
                foreach ($users as $k => $v) {
                    $u = $this->table->find('id', $v)[0];
                    $usr[$u->id] = $u->name;
                }
            }
            return [$usr, []];
        }
    }
  
}
