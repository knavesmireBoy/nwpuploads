<?php

namespace NorthWolds\Entity;

class User extends Entity
{
  const BROWSER = 1; // 00000001
  const MANAGER = 2; // 00000010
  const CLIENT = 4; // 00000100
  const CLIENT_ADMIN = 8; // 00001000
  const ADMIN = 16; // 00010000; edit user permissions
  const SUPER = 32; // 00100000; ; edit user permissions AND delete user (must ALSO be account_editor) ie 48
  const SUPERADMIN = 64; // 01000000 (use permissions : 80)
  private $roleid;
  protected $table;
  protected $roletable;
  protected $userroletable;
  protected $clienttable;
  protected $roles = ['Browser', 'Manager', 'Client', 'Client Admin', 'Admin'];
  protected $self;
  //public $permissions;
  public $password;
  public $id;
  public $name;
  public $email;
  public $client_id;

  public function __construct(\Ninja\DatabaseTable $table, \Ninja\DatabaseTable $client, \Ninja\DatabaseTable $userrole, \Ninja\DatabaseTable $role)
  {
    $this->table = $table;
    $this->userroletable = $userrole;
    $this->roletable = $role;
    $this->clienttable = $client;
  }

  public function setSelf(bool $bool)
  {
    $this->self = $bool;
  }

  public function getSelf()
  {
    return $this->self;
  }

  public function edit()
  {
    return false;
  }

  public function editPayload($id = '')
  {
    return [];
  }

  public function loadPayload()
  {
    return [];
  }

  //convert ids into table structure [userid, roleid]
  protected function getAllRoles(array $ids)
  {
    if (empty($ids)) {
      return [];
    }
    $cb = partial([$this, 'find'], 'userroletable', 'userid');
    $roles = array_map($cb, $ids);
    $roles = safeFilter($roles, fn($o) => $o->roleid);
    return array_map('get_object_vars', $roles);
  }
  protected function getAdminRoles(array $roles = [])
  {
    $cb = composer(partial('equals', 'Client Admin'), curry2('getter')('roleid'));
    return !empty($roles) ? safeFilter($roles, $cb) : $roles;
  }

  public function validateDelete()
  {
    $ids = $this->getUserIds();
    $msg = '';
    if (empty($ids)) {
      return $msg;
    }
    $admin = isApproved($_SESSION['role'], 'ADMIN');
    $roles = $this->getAllRoles($ids);
    if (count($roles) === 1) {
      $msg = $admin ? '_last' : 'last';
    }
    $adminroles = $this->getAdminRoles($roles);
    if (!$msg && count($adminroles) === 1) {
      $ids = array_column($adminroles, 'userid');
      $key = in_array($this->id, $ids) ? 'lasteditor' : '';
      $msg = $admin ? '_last' : $key;
    }
    return $msg;
  }

  private function validateRole($role)
  {
    $details = $this->getDetails();

    if (empty($details)) {
      return $role;
    }
    $admin = isApproved($details['role'], 'ADMIN');
    if ($admin && $this->id == $details['id']) {
      return $this->roleid;
    }
    $ids = $this->getUserIds();
    if (in_array($role, $this->roles)) {
      $roles = $this->getAllRoles($ids);
      $roles = $admin ? $roles : $this->getAdminRoles($roles);

      if (!empty($roles)) {
        $i = array_search($role, $this->roles);
        $j = array_search($this->roleid, $this->roles);

        if (($i < $j) && preg_match('/admin/i', $this->roleid)) { //demotion
          if (count($roles) === 1) {
            return $admin ? '_last' : 'lastadmin';
          }
        }
      }
    }
    return $role;
  }

  protected function fetchAllRoles(array $keys = [], array $selectedRoles = []): array
  {
    //Build the list of all roles
    $rows = $this->roletable->findAll(null, 0, 0, \PDO::FETCH_ASSOC);
    if ($keys !== []) {
      $rows = reAssoc($rows, $keys, 'id', 'description', [], 0, 0);
    }
    foreach ($rows as $row) {
      $roles[] = ['id' => $row['id'], 'description' => $row['description'], 'selected' => in_array($row['id'], $selectedRoles)];
    }
    return $roles;
  }

  protected function getRole($userid = null): ?string
  {
    $id = $userid ? $userid : $this->id;
    $res = $this->fetch('userroletable', 'userid', $id);
    //$this->roleid = $userid ? null : $res->roleid;
    $this->roleid = $res->roleid ?? null;
    return $this->roleid;
  }

  public function getRoles(int $userid, string $roleid)
  {
    return [];
  }

  public function setRole(string $role, int $userid = 0)
  {
    $uid = $userid ? $userid : $this->id;
    //if $action is true insert otherwise update
    $action = empty($this->userroletable->find('userid', $uid));
    //A) if validation fails return a key for query eg 'last' header(Location: /user/load/last)
    $role = $this->validateRole($role);
    if (in_array($role, $this->roles)) {
      $this->userroletable->save(['userid' => $this->id, 'roleid' => $role], $action);
      $this->roleid = $userid ? null : $role;
      //B if validation succeeds set to empty string (or success?)
      $role = '';
    }
    return $role;
  }

  private function validateDom($cid, $dbrecord, $ename, $postdom, $insertID)
  {
    $client = $cid ? $this->clienttable->find('id', $cid) : [];
    $key = '';
    $flag = isset($_COOKIE['leave']) ? false : true;
    $setcookie = doSetCookie($flag);
    //ADMIN moving or switching a user to a client
    if (isset($client[0])) {
      $details = $this->getDetails();
      if (isApproved($details['role'], 'admin')) {
        $key = '_denied';
      }
      if (isset($details['colleagues']) && !$details['colleagues']) {
        $key = '_last';
      }
      if ($key) {
        reLocate("/user/load/$key");
      }
      $postdom = $client[0]->domain; //sync
      $data = ['id' => $this->id, 'email' => "$ename@$postdom", 'client_id' => $client[0]->id];
    } else {
      $client = $this->clienttable->getEntity();
      if ($client->domainAvailable($postdom)) {
        $data = ['id' => $this->id, 'email' => "$ename@$postdom", 'name' => $dbrecord['name'], 'client_id' => null];
        /*
        if ($flag) {
          $this->setCookie($data, ['name', 'email'], true);
          $setcookie('leave');
          reLocate('/user/loadbridge/leave/'  . $this->id);
        } else {
          $setcookie('leave');
          $this->setCookie($data, ['name', 'email'], false);
        }
          */
      } else {
        if ($insertID) { // a new
          $this->table->delete('id', $insertID);
          reLocate('/user/load/impostor');
        } else { //or existing user (freelancer) attempting to leave
          $client = $this->find('clienttable', 'domain', $postdom);
          $postdom = $client->domain;
          //silently restore to client as
          reLocate('/user/load/traitor');
          $data = ['id' => $this->id, 'email' => "$ename@$postdom", 'name' => $dbrecord['name'], 'client_id' => nullify($client->id)];
        }
      }
    }
    return $data;
  }


  public function domainCheck($postdom)
  {
    return $this->find('clienttable', 'domain', $postdom);
  }

  public function parseEmail($e)
  {
    $f = composer(partial('substr', $e, 0), curry2('strpos')('@'));
    $name = $f($e);
    list($dom, $com) = parseEmail($e);
    return [$name, $dom, $com];
  }

  public function updateUserDomain(?int $cid, array $postdata, int $insertID = 0)
  {
    $email = $cid ? $this->email : $postdata['email'];
    list($name, $dom, $com) = $this->parseEmail($email);
    $postdom = "$dom.$com";
    $details = $this->getDetails();
    $domain = $details['domain'];
    $data = $this->validateDom($cid, $postdata, $name, $postdom, $insertID);
    if ($cid && $domain && ($postdom !== $domain)) {
      reLocate('/user/load/domain');
    } else {
      return $data;
    }
  }

  public function updatePassword($password)
  {
    if ($password) {
      $this->table->save(['id' => $this->id, 'password' => md5($password . 'uploads')]);
    }
  }

  public function hasPermission(array $allowed)
  {
    $role = $this->getRole();
    $found = array_search($role, $allowed);
    return is_numeric($found) ? true : null;
  }

  public function checkPermission(int $permission)
  {
    $lib = [1 => 'Browser', 2 => 'Manager', 4 => 'Client', 8 => 'Client Admin', 16 => 'Admin'];
    $libr = array_flip($lib);
    $role = $this->getRole();
    $int = isset($libr[$role]) ? $libr[$role] : 0;
    return $int & $permission;
  }

  public function getDetails($prop = '')
  {
    $role = $this->getRole();
    $key = 'id';
    $client = null;
    $base = [$key => $this->id, 'name' => $this->name, 'email' => $this->email, 'role' => $role, 'client_id' => null];
    $clientdetails = ['clientname' => '', 'tel' => '', 'domain' => ''];
    $users = [];
    if (!empty($role)) {
      if ($prop === 'owner') {
        $key = 'ownerid';
      } else if ($prop) {
        return isset($this->{$prop}) ? $this->{$prop} : [];
      }
      if ($this->client_id) {
        $client = $this->fetch('clienttable', 'id', $this->client_id);
        $users = $this->table->find('client_id', $this->client_id, null, 0, 0, \PDO::FETCH_ASSOC);
        $clientdetails = ['client_id' => $this->client_id, 'clientname' => $client->name, 'tel' => $client->tel, 'domain' => $client->domain, 'colleagues' => count($users) > 1];
      }
      return [...$base, ...$clientdetails];
    }
    return [];
  }

  public function fromDomain($domain, $mode = \PDO::FETCH_CLASS)
  {
    return $this->clienttable->find('domain', $domain, 'name', 0, 0, $mode)[0];
  }

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
}
