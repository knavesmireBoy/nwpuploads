<?php

namespace NorthWolds\Entity;


class ClientAdmin extends User
{
    //return false to present list of users for logged in client
    public function preEdit($flag = true)
    {
        return false;
    }

    public function postEdit()
    {
      return '';
    }
  
    /*
    public function getUserIds($roles = null)
    {
      $details = $this->getDetails();
      $res = [];
      if ($details['client_id']) {
        $users = $this->table->find('client_id', $this->client_id);
        $res = array_map(fn($o) => $o->id, $users);
      } else {
        return [];
      }
      if (is_bool($roles)) {
        $ret = $this->getAllRoles($res);
        return $roles ? $ret : $this->getAdminRoles($ret);
      }
      return $res;
    }
      */

    protected function validateDom($cid, $record, $postdata, $insertId = 0)
    {
        
        dump('client_admin');
        list($name, $dom, $com) = $this->parseEmail($record['email']);
        list($ename, $edom, $ecom) = $this->parseEmail($postdata['email']);
        if ("$edom.$ecom" === "$dom.$com") {
            $postdata['email'] = "$ename@$edom";
            $postdata['client_id'] = $cid;
            return $postdata;
        }
        return false;
    }

    protected function setClientEmail($cid, $name, $data)
    {
        $client = $this->fetch('clienttable', 'id', $cid);
        $domain = $client->domain;
        $data['email'] = "$name@$domain";
        return $data;
    }

    public function updateDomain($key, $uid, $default)
    {
        return function (?int $cid, array $postdata, int $id = 0) use ($key, $uid, $default) {
            list($name, $dom, $com) = $this->parseEmail($postdata['email']);
            $dbrecord = $this->fetch('TABLE', 'id', $this->id);
            if (isset($dbrecord['email'])) { //existing
                if(!$cid || $dbrecord['client_id'] != $cid){
                    reLocate("/user/load/_cadmin");
                }
                $data = $this->validateDom($cid, $dbrecord, $postdata);
               
                if (!$data) {
                    $this->setCookie(['flash'=>"key=domain"], ['flash'], true);
                    reLocate("/user/loadbridge");
                }
                return $data;
            } else { //new
                return $this->setClientEmail($cid, $name, $postdata);
            }
        };
    }

    public function setRole(string $role, mixed $flag)
    {
        //if $action is true insert otherwise update
        $action = empty($this->userroletable->find('userid', $this->id));
        //A) if validation fails return a key for query eg 'last' header(Location: /user/load/last)
        $role = $this->validateRole($role);

        if (in_array($role, $this->roles)) {
            $this->userroletable->save(['userid' => $this->id, 'roleid' => $role], $action);
            $this->roleid = $role;
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

    public function editPayload($id = '')
    {
        return [
            'calltext' => 'Delete User',
            'callroute' => "/user/delete/$id",
            'retour' => '_return2list.html.php'
        ];
    }

    public function loadPayload($id = '')
    {
        return [
            'callroute' => '/user/add/',
            'calltext' => 'Add New User',
            'selected' => true, //!!foregoes the drop down menu required by Admin
            'retour' => '_return2uploads.html.php'
        ];
    }

    public function getRoles($userid = '')
    {
        $f = composer(negate(curry2('equals')('Admin')), curry2('getter')('id'));
        $roleid = $this->getRole($userid);
        $roles = $this->fetchAllRoles($this->roles, [$roleid]);
        return safeFilter($roles, $f);
    }

    public function presentList($userId)
    {
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
