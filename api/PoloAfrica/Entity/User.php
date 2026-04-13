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
  private $table;
  private $roletable;
  private $roleid;
  private $userroletable;
  //public $permissions;
  public $password;
  public $id;
  public $name;
  public $email;
  public $client_id;

  public function __construct(\Ninja\DatabaseTable $table, \Ninja\DatabaseTable $userrole, \Ninja\DatabaseTable $role)
  {
    $this->table = $table;
    $this->userroletable = $userrole;
    $this->roletable = $role;
  }

  public function hasPermission(int $permission)
  {
    //return $this->permissions & $permission;
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
    $res = $this->userroletable->find('userid', $this->id);
    if (!empty($res)) {
      if($prop){
        return $this->{$prop};
      }
      return ['id' => $this->id, 'name' => $this->name, 'email' => $this->email, 'client_id' => $this->client_id, 'role' => $res[0]->roleid];
    }
    return null;
  }
}
