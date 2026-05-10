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

  protected function getRole(): ?string
  {
    //$res = $this->fetch('userroletable', 'userid', $this->id);
    $res = $this->userroletable->find('userid', $this->id)[0];
    $this->roleid = nullify($res->roleid);
    return $this->roleid;
  }

  public function getRoles()
  {
    $this->roleid = $this->getRole();
    if (!preg_match('/admin/', $this->roleid)) {
      return [];
    }
    $roles = $this->fetchAllRoles($this->roles, [$this->roleid]);
    $cb = preg_match('/client/i', $this->roleid) ? composer(negate(curry2('equals')('Admin')), curry2('getter')('id')) : 'identity';
    return safeFilter($roles, $cb);
  }

  public function setRole(string $role)
  {
    $action = empty($this->userroletable->find('userid', $this->id));
    if (in_array($role, $this->roles)) {
      $this->userroletable->save(['userid' => $this->id, 'roleid' => $role], $action);
      $this->roleid = $role;
    }
  }

  private function validateDom($cid, $dbrecord, $name, $postdom, $insertID)
  {
    $client = $this->clienttable->find('id', $cid);
    //admin moving or switching a user to a client
    if (isset($client[0])) {
      $postdom = $client[0]->domain;
      $data = ['id' => $this->id, 'email' => "$name@$postdom", 'client_id' => $client[0]->id];
    } else {
      $client = $this->clienttable->getEntity();
      if ($client->validateDom($postdom)) {
        $data = ['id' => $this->id, 'email' => "$name@$postdom", 'client_id' => null];
      } else {
        if ($insertID) { // a new
          $this->table->delete('id', $insertID);
          reLocate('/user/load/impostor');
        } else { //or existing user (freelancer) attempting to set a blacklisted domain
          //silently revert, or send a message
          $libkey = 'mover';
          $data = ['id' => $this->id, 'email' => $dbrecord['email']];
        }
      }
    }
    return $data;
  }

  public function parseEmail($e)
  {
    $f = composer(partial('substr', $e, 0), curry2('strpos')('@'));
    $name = $f($e);
    list($dom, $com) = parseEmail($e);
    return [$name, $dom, $com];
  }

  public function updateUserDomain(?int $cid, array $dbrecord, int $insertID = 0)
  {
    list($name, $dom, $com) = $this->parseEmail($this->email);
    $postdom = "$dom.$com";
    $details = $this->getDetails();
    $domain = $details['domain'];
    $data = $this->validateDom($cid, $dbrecord, $name, $postdom, $insertID);
    if ($domain && $postdom !== $domain) {
      reLocate('/user/load/domain');
    } else {
      if ($data) {
        $this->table->save($data);
        reLocate('/user/load/');
      }
    }
  }

  public function updatePassword($password)
  {
    $this->table->save(['id' => $this->id, 'password' => md5($password . 'uploads')]);
  }

  public function hasPermission(array $allowed)
  {
    $role = $this->getRole();
    $found = array_search($role, $allowed);
    return is_numeric($found) ? $found : null;
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
      }

      return [$key => $this->id, 'name' => $this->name, 'email' => $this->email, 'role' => $role,  'client_id' => $this->client_id, 'clientname' => $client->name ?? '', 'tel' => $client->tel ?? '', 'domain' => $client->domain ?? '', 'colleagues' => count($users) > 1];
    }
    return [];
  }

  public function fromDomain($domain, $mode = \PDO::FETCH_CLASS)
  {
    return $this->clienttable->find('domain', $domain, 'name', 0, 0, $mode)[0];
  }

  public function getUserIds()
  {
    $details = $this->getDetails();
    if ($details['client_id']) {
      $users = $this->table->find('client_id', $this->client_id);
      return array_map(fn($o) => $o->id, $users);
    } else {
      return [];
    }
  }
}
