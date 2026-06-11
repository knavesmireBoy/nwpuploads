<?php

namespace NorthWolds\Entity;

class User extends Entity implements \SplSubject
{
  protected $roles = ['Browser', 'Manager', 'Client', 'Client Admin', 'Admin'];
  protected $self;
  protected $exit;
  public $password;
  public $id;
  public $name;
  public $email;
  public $client_id;

  /*
Not actually using the observer pattern as we're using cookies for "flash" messaging
rather than calling an update method of the observer, but keeping this as an example of extending the \Spl Library
  */

  protected \SplObjectStorage $observers;
  protected array $users = [];
  protected static $instance;

  /*
  https://medium.com/codex/observer-pattern-in-php-8-569c71dd7837
  public static function getInstance($t, $c, $u, $r): self
  {
    if (self::$instance === null) {
      self::$instance = new self($t, $c, $u, $r);
    }
    return self::$instance;
  }
  private function __clone() {}
  public function createUser(User $user): void
  {
    $this->users[] = $user;
    $this->notify();
  }
*/
  public function __construct(\Ninja\DatabaseTable $table, \Ninja\DatabaseTable $client, \Ninja\DatabaseTable $userrole, \Ninja\DatabaseTable $role, string $exit)
  {
    $this->table = $table;
    $this->userroletable = $userrole;
    $this->roletable = $role;
    $this->clienttable = $client;
    $this->observers = new \SplObjectStorage;
    $this->exit = $exit;
  }

  public function attach(\SplObserver $observer): void
  {
    $this->observers->attach($observer);
  }
  public function detach(\SplObserver $observer): void
  {
    $this->observers->detach($observer);
  }
  public function notify(): void
  {
    foreach ($this->observers as $observer) {
      $observer->update($this);
    }
  }
  /*

*/

protected function setClientEmail1($cid, $data, $record)
{
  $client = $this->fetch('clienttable', 'id', $cid);
  list($name, $dom, $com) = $this->parseEmail($record['email']);
  $recorddom = "$dom.$com";
  list($ename, $dom, $com) = $this->parseEmail($data['email']);
  $edom = "$dom.$com";
  $domain = $client->domain ?? null;

  if (!$domain /*&& $this->findDomain("$dom.$com")*/) { //leaving? or never a client
    // $domain = "$dom.$com";
    $data['client_id'] = null;
  } else {
    $client = $this->findDomain("$dom.$com");
    $domain = isset($client) && $client->id == $cid;
    //reset email
  }

  list($ename, $dom, $com) = $this->parseEmail($data['email']);
  $domain = "$dom.$com";
  $data['email'] = "$ename@$domain";

  return [...$data, 'client_id' => $cid];
}

  protected function setClientEmail($cid, $data, $record)
  {
    $client = $this->fetch('clienttable', 'id', $cid);
    list($ename) = $this->parseEmail($data['email']);
    $domain = $client->domain ?? null;

    if ($domain) {
      $data['email'] = "$ename@$domain";
    }
    return [...$data, 'client_id' => nullify($cid)];
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

  protected function validateDomField($cid, $dbrecord, $postdata, $insertID)
  {
    $client = $this->fetch('clienttable', 'id', $cid);
    list($ename, $dom, $com) = $this->parseEmail($postdata['email']);
    $postdom = "$dom.$com";
    $key = '';
    if ($client) {
      $details = $this->getDetails();
      $key = isApproved($details['role'], 'ADMIN') ? '_denied' : $key;
      $data = $this->setClientEmail($cid, $postdata, $dbrecord);
    } else {
      $client = $this->clienttable->getEntity();
      if ($client->domainAvailable($postdom)) {
        $name = $postdata['name'] ? $postdata['name'] : $dbrecord['name'];
        $data = ['id' => $this->id, 'email' => "$ename@$postdom", 'name' => $name, 'client_id' => null];
      } else {
        if ($insertID) { // a new
          $this->table->delete('id', $insertID);
          $key = '_impostor';
        } else { //or existing user (freelancer) attempting to swap clients
          $key = $this->self ? 'traitor' : '_traitor';
        }
      }
    }
    if ($key) {
      $this->setCookie(['flash' => "key=$key"], ['flash'], true);
      reLocate($this->exit);
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
    $users = $this->table->find('client_id', $this->client_id);
    $res = array_map(fn($o) => $o->id, $users);
    if (is_bool($roles)) {
      $ret = $this->getAllRoles($res);
      return $roles ? $ret : $this->getAdminRoles($ret);
    }
    return $res;
  }

  public function findDomain($postdom)
  {
    return $this->find('clienttable', 'domain', $postdom);
  }

  public function fromDomain($domain, $mode = \PDO::FETCH_CLASS)
  {
    $client = $this->clienttable->find('domain', $domain, 'name', 0, 0, $mode);
    return $client[0] ?? null;
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

  public function setRole(string $role, mixed $flag)
  {
    if (!empty($this->roletable->find('id', $role))) {
      $this->userroletable->save(['userid' => $this->id, 'roleid' => $role], $flag && is_bool($flag));
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

  public function updateDomain($uid, $default)
  {
    if ($default) {
      return function (?int $cid, array $postdata, int $id = 0) use ($uid) {

        $dbrecord = $this->fetch('TABLE', 'id', $uid);
        $data = $this->validateDomField($cid, $dbrecord, $postdata, $id);

        $details = $this->getDetails();
        $domain = $details['domain'] ?? '';

        if ($cid && !$domain) {
          $this->setCookie([...$data, 'flash' => "key=join&id=$uid&client_id=$cid"], ['flash', 'name', 'email', 'client_id'], true);
          reLocate($this->exit);
        }
        return $data;
      };
    }
    return function (?int $cid, array $postdata, int $id = 0) use ($uid) {
      $client = $this->fetch('clienttable', 'id', $cid);
      $clientdata = $cid ? $client->validateDomain($postdata['email'], 'client_id') : [];
      $relocate = !empty($clientdata) && !$clientdata['client_id'] ? true : false;
      $postdata = [...$postdata, ...$clientdata];
      if ($relocate) {
        $this->setCookie($_COOKIE, ['name', 'email', 'client_id'], false);
        $this->setCookie(['flash' => "key=_sync&id=$uid"], ['flash'], true);
        reLocate($this->exit);
      }
      return $postdata;
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
          $this->setCookie(['flash' => "key=undef&id=$id&arg=$prop"], ['flash'], true);
          reLocate($this->exit);
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
