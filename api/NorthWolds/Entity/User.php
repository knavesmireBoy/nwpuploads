<?php

namespace NorthWolds\Entity;

class User extends Entity
{
  /*
  const BROWSER = 1; // 00000001
  const MANAGER = 2; // 00000010
  const CLIENT = 4; // 00000100
  const CLIENT_ADMIN = 8; // 00001000
  const ADMIN = 16; // 00010000; edit user permissions
  const SUPER = 32; // 00100000; ; edit user permissions AND delete user (must ALSO be account_editor) ie 48
  const SUPERADMIN = 64; // 01000000 (use permissions : 80)
  public $permissions;
*/
  protected $roleid; //NOTE ::getDetails returns a role field BUT property is $roleid
  protected $table;
  protected $roletable;
  protected $userroletable;
  protected $clienttable;
  protected $roles = ['Browser', 'Manager', 'Client', 'Client Admin', 'Admin'];
  protected $self;
  protected $home;
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

  protected function validateRole($role)
  {
    return $role;
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

  protected function countRoles($users, $id = false)
  {
    $res = array_map(fn($o) => $o['id'], $users);
    $ret = $this->getAllRoles($res);

    if ($id) {
      $adminroles = $this->getAdminRoles($ret);
      if (count($adminroles) === 1 && $id && $id == $adminroles[0]['userid']) {
        return true;
      }
      return false;
    }

    return count($ret);
  }

  protected function validateDom($cid, $dbrecord, $ename, $postdom, $insertID)
  {
    $client = $cid ? $this->clienttable->find('id', $cid) : [];
    $key = '';
    if (isset($client[0])) {
      $details = $this->getDetails();
      if (isApproved($details['role'], 'admin')) {
        reLocate("/user/load/_denied");
      }
      $postdom = $client[0]->domain; //sync
      $data = ['id' => $this->id, 'email' => "$ename@$postdom", 'client_id' => $client[0]->id];
    } else {
      $client = $this->clienttable->getEntity();
      if ($client->domainAvailable($postdom)) {
        $data = ['id' => $this->id, 'email' => "$ename@$postdom", 'name' => $dbrecord['name'], 'client_id' => null];
      } else {
        if ($insertID) { // a new
          $this->table->delete('id', $insertID);
          reLocate('/user/load/_impostor');
        } else { //or existing user (freelancer) attempting to swap clients
          $key = $this->self ? 'traitor' : '_traitor';
          reLocate("/user/load/$key");
          $data = ['id' => $this->id, 'email' => "$ename@$postdom", 'name' => $dbrecord['name'], 'client_id' => nullify($client->id)];
        }
      }
    }
    return $data;
  }

  public function presentList($userId)
  {
      return [[], []];
  }

  public function getRoles($userid = '')
  {
    return [];
  }

  public function getUserIds($roles = null)
  {
      return [];
  }

  public function findDomain($postdom)
  {
    return $this->find('clienttable', 'domain', $postdom);
  }

  public function fromDomain($domain, $mode = \PDO::FETCH_CLASS)
  {
    return $this->clienttable->find('domain', $domain, 'name', 0, 0, $mode)[0];
  }

  public function setSelf(bool $bool)
  {
    $this->self = $bool;
  }

  public function getSelf()
  {
    return $this->self;
  }

  public function preEdit($flag = true)
  {
    return $flag;
  }

  public function postEdit()
  {
    return $this->self ? 'success' : '';
  }

  public function loadPayload($id = '')
  {
    return [
      'retour' => '_return2uploads.html.php'
    ];
  }

  public function editPayload($id = '')
  {
    return [
      'retour' => '_return2uploads.html.php'
    ];
  }

  public function setRole(string $role)
  {
    if (!empty($this->roletable->find('id', $role))) {
      $this->userroletable->save(['userid' => $this->id, 'roleid' => $role]);
      return ''; //ok
    }
    return $role;
  }

  public function parseEmail($e)
  {
    $f = composer(partial('substr', $e, 0), curry2('strpos')('@'));
    $name = $f($e);
    list($dom, $com) = parseEmail($e);
    return [$name, $dom, $com];
  }

  public function updateDomain($key, $uid, $override)
  {
    return function (?int $cid, array $postdata, int $id = 0) use ($key, $uid, $override) {
      $email = $cid ? $this->email : $postdata['email'];
      list($name, $dom, $com) = $this->parseEmail($email);
      $postdom = "$dom.$com";
      $details = $this->getDetails();
      $domain = $details['domain'] ?? '';
      $data = $this->validateDom($cid, $postdata, $name, $postdom, $id);
      if ($cid && $domain && ($postdom !== $domain)) {
        reLocate("/user/load/$key");
      } else {
        return $data;
      }
    };
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
        $pass = isset($this->{$prop});
        if ($pass) {
          return $this->{$prop};
        } else {
          $id = $this->id;
          reLocate("/user/loadbridge/undef/$id/$prop");
        }
      }
      if ($this->client_id) {
        $client = $this->fetch('clienttable', 'id', $this->client_id);
        $users = $this->table->find('client_id', $this->client_id, null, 0, 0, \PDO::FETCH_ASSOC);

        $bool = $this->countRoles($users, $this->id) && count($users) > 1;

        $clientdetails = ['client_id' => $this->client_id, 'clientname' => $client->name, 'tel' => $client->tel, 'domain' => $client->domain, 'colleagues' => count($users) > 1, 'administrator' => $bool];
      }
      return [...$base, ...$clientdetails];
    }
    return [];
  }

  //sort of validate delete; if it returns anything other than a empty string you cannot delete
  public function delete($id)
  {
    return '';
  }
}
