<?php

namespace PoloAfrica\Entity;

class User
{
  const BROWSER = 1; // 00000001
  const CONTENT_EDITOR = 2; // 00000010
  const PHOTO_EDITOR = 4; // 00000100
  const CHIEF_EDITOR = 8; // 00001000
  const ACCOUNT_EDITOR = 16; // 00010000; edit user permissions
  const ADMIN = 32; // 00100000; ; edit user permissions AND delete user (must ALSO be account_editor) ie 48
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

  protected function fetch($t, $prop, $val, ...$rest)
  {
    $ret = [];
    if ($val) { //safeguard against missing values
      if (strtoupper($t) === $t) {
        $t = strtolower($t);
        $ret = $this->{$t}->find($prop, $val, null, 0, 0, \PDO::FETCH_ASSOC);
      } else {
        $ret = $this->{$t}->find($prop, $val, ...$rest);
      }
    }
    return empty($ret) ? null : $ret[0];
  }

  public function hasPermission($allowed)
  {
    $res = $this->fetch('userroletable', ' userid', $this->id);
    $role = $res->roleid ?? null;
    $found = array_search($role, $allowed);
    return is_numeric($found) ? $found : null;
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
    $res = $this->fetch('userroletable', 'userid', $this->id);
    $role = $res->roleid ?? null;
    if (!empty($res)) {
      if ($prop) {
        return $this->{$prop};
      }
      if ($this->client_id) {
        $client = $this->fetch('clienttable', ' id', $this->client_id);
      }
      return ['id' => $this->id, 'name' => $this->name, 'email' => $this->email, 'role' => $role, 'client' => $client->name ?? '', 'tel' => $client->tel ?? '', 'client_id' => $this->client_id];
    }
    return null;
  }
}
