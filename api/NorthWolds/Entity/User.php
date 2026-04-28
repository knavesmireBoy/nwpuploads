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

  private function getRole(){
    $res = $this->fetch('userroletable', 'userid', $this->id);
    return $res->roleid ?? null;
  }

  public function hasPermission(int $permission)
  {
    $lib = [1 => 'Browser', 2 => 'Manager', 4 => 'Client', 8 => 'Client Admin', 16 => 'Admin'];
    $libr = array_flip($lib);
    $role = $this->getRole();
    $int = isset($libr[$role]) ? $libr[$role] : 0;
    return $int & $permission;
     /*
    $found = array_search($role, $allowed);
    return is_numeric($found) ? $found : null;
    */
  }

  public function checkPermission(int $permission)
  {
    //return $this->hasPermission($permission) && $this->permissions >= $permission;
  }

  public function canEdit()
  {
    // return $this->permissions >= 2;
  }

  public function getPermission()
  {
    //return $this->permissions;
  }

  public function getDetails($prop = '')
  {
    $role = $this->getRole();
    $client = null;
    if (!empty($res)) {
      if ($prop) {
        return $this->{$prop};
      }
      if ($this->client_id) {
        $client = $this->fetch('clienttable', 'id', $this->client_id);
      }
      return ['ownerid' => $this->id, 'name' => $this->name, 'email' => $this->email, 'role' => $role,  'client_id' => $this->client_id, 'clientname' => $client->name ?? '', 'tel' => $client->tel ?? '', 'domain' => $client->domain ?? ''];
    }
    return null;
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
