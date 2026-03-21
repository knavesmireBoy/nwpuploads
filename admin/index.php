<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/access.inc.php';
//NOTE arrow functions not introduced until PHP 7.4; default mac installation is 7.3xx
function fix()
{
  include CONNECT;
  reOrderTable($pdo);
  reAssignClient($pdo);
}

function query()
{
  $lib = ['nousers' => "<Unable to find any users", "addnotice" => "Please fill required fields", "selectuser" => "Please select a user for editing", "domainflag" => "Cannot assign this user to a new client", "lastuser" => "To remove this last user, please delete the client instead", "denied" => "You do not have the privileges to delete this user", "deniedbyclient" => "There must be at least one administrator role, please assign another user before removing your credentials from the database", "access" => "You do not have the privileges to add a user", "deniedbyadmin" => "Cannot delete this user until a new client admin role is assigned to this client", "self" => "Only a peer can perform this deletion", "freelancer" => "Cannot assign this domain", 'addno' => 'You do not have the required privilges to add a user'];
  $query = explode('=', $_SERVER["QUERY_STRING"]);
  $q = $query[1] ?? $query[0];
  return $lib[$q] ?? NULL;
}

function unsetDetails($bool = false)
{
  $setcookie = doSetCookie($bool);
  $setcookie('email', $_POST['email'] ?? '');
  $setcookie('username', $_POST['name'] ?? '');
}

function queryClient($mixed = false)
{
  //NOTE id AS employer AND domain in that order as expected by list($employer, $domain)
  $dom = fromStrPos();
  $sql = "SELECT client.id AS employer, domain, user.id, user.email, user.name FROM client LEFT JOIN user ON $dom = client.domain";
  $options = ['email' => " WHERE user.email=:aux", 'id' => " WHERE user.id=:aux", 'employer' => " WHERE client.id=:aux"];

  if (is_array($mixed)) {
    $mixed = $mixed[0];
    $like = "SELECT client.id AS employer, domain FROM client WHERE client.domain LIKE '$mixed%'";
    return $like;
  } else {
    $x = $options[strtolower($mixed)] ?? '';
    return  $sql . $x;
  }
}

//object and prop or; no prop object MUST be a domain
function isEmployer($o, $p = '')
{
  $id = null;
  $flag = $p && checkUpper($p);
  //https://stackoverflow.com/questions/2628138/how-to-select-domain-name-from-email-address
  if (!$p) {
    $sql = queryClient($o);
  }
  if ($p === 'email') {
    $sql = queryClient('email');
    $id = $o[$p] ?? 0;
  }
  if ($p === 'id') {
    $sql = queryClient('id');
    $id = $o[$p] ?? 0;
  }
  if (preg_match('/employer/i', $p)) {
    if (is_array($o)) {
      $id = $o[$p] ?? 0;
      $sql = "SELECT client.id, domain FROM client WHERE client.id =:aux";
    } else {
      $p = strtolower($p);
      $sql = queryClient('employer');
      $id = $o->$p ?? 0;
    }
  }

  return function () use ($id, $sql, $flag) {
    include CONNECT;
    $st = $pdo->prepare($sql);
    if (isset($id)) {
      $st->bindValue(':aux',  $id);
    }
    doPreparedQuery($st, 'Error fetching client.');
    return $flag ? $st->fetchAll(PDO::FETCH_NUM) : $st->fetch(PDO::FETCH_NUM);
  };
}
//doozy
function queryEmail($editor, $obj)
{
  $ecom = null;
  $edom = null;
  $hasEmployer = isEmployer($obj, 'id');
  list($dom, $com) = parseEmail($obj['email'] ?? '');
  if ($editor) {
    list($edom, $ecom) = parseEmail($_SESSION['email']);
    $domchange = $dom !== $edom;
  } else {
    list($_, $domain) = $hasEmployer();
    list($edom, $ecom) = parseEmail($domain);
    //need to obtain existing email;
    $domchange = $domain ? ($dom !== $edom || $com !== $ecom) : null;
  }
  //forces a admin/user combo to query the database for existing domain
  $domchange = !isset($domchange) ? true : $domchange;
  $comchange = $ecom ? $com !== $ecom : null;
  return [$domchange, $comchange, $dom, $com];
}

function presentList($role, $flag = 'admin')
{
  $users = [];
  $client = [];
  if (isApproved($role, $flag)) {
    include CONNECT;
    $sqlu = "SELECT user.id, user.name FROM user LEFT JOIN client ON user.client_id=client.id WHERE client.domain IS NULL ORDER BY name";

    $st = doQuery($pdo, $sqlu, "Error retrieving details");
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
      $users[$row['id']] = $row['name'];
    }
    $st = doQuery($pdo, "SELECT name, domain, tel FROM client ORDER BY name", "Database error fetching clients");
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
      $client[$row['domain']] = $row['name'];
    }
    return [$users, $client];
  }
}

function checkCurrentDetails($id, $p = '')
{
  include CONNECT;
  $sql = "SELECT user.name, user.email, client.domain FROM user LEFT JOIN client ON user.client_id=client.id WHERE user.id=:id ORDER BY name";
  $st = $pdo->prepare($sql);
  $st->bindValue(":id", $id);
  doPreparedQuery($st, "Error fetching user details");
  $row = $st->fetch(PDO::FETCH_ASSOC);
  return  $p ? $row[$p] : $row;
}

function canEdit($id, $postemail, $priv)
{
  $logemail = strtolower($_SESSION['email']); //may be admin/user, client admin/user or user/user
  $row = checkCurrentDetails($id);
  $email = $row['email'] ?? '';
  $dbemail = NULL;
  if ($row) {
    $dbemail = strtolower($email);
  }
  $postemail = $postemail ? strtolower($postemail) : $logemail;
  return [$dbemail !== $postemail, $logemail === $dbemail, $row['domain'] ?? '', isApproved($priv, 'admin'), $row['name'] ?? ''];
}

function verifyDom($editor, $admin, $domain, $employerid)
{
  list($domchange, $comchange, $dom, $com) = queryEmail($editor, $_POST);
  //validating domain: have these functions return TRUE to indicate failure
  $fn = isEmployer([$dom]);
  list($cid) = $fn();
  $fn =  isEmployer(toObject(['employer' => $cid]), 'EMPLOYER');
  $count = count($fn()) > 1;
  $clientFunc = function ($change, $arg) {
    // return false;
    return $change && $arg;
  };
  $adminFunc = function ($change, $editor) {
    return $change ? $editor : false;
  };
  $clientcb = $admin ? curry2($clientFunc)($employerid || $cid) : 'identity';
  $admincb = curry2($adminFunc)($editor);
  $usercb = curry2('likeDomain')($dom);
  $actions = ['client' => $clientcb, 'admin' => $admincb, 'user' => $usercb];
  $k = searchGroup(true, [$domain, $admin, true], ['client', 'admin', 'user']);
  $validateDom = $actions[$k];
  //the returned curried function expects a $change boolean: $domchange || $comchange
  $domfail = $validateDom($domchange || $comchange || $cid != $employerid);
  return [$domfail, "$dom.$com", $domchange, $employerid];
}

//$key expected to be freelance id (int) or domain (str)
function filterUsers($key, $pagetitle, $error = '')
{
  $users = [];
  $namechange = $_GET['namechange'] ?? NULL;
  $selected = true;
  include CONNECT;
  $sql = "SELECT user.id, user.name, user.email, client.domain FROM user LEFT JOIN client ON user.client_id=client.id WHERE client.domain=:dom ORDER BY name";
  $st = $pdo->prepare($sql);
  $st->bindValue(":dom", $key);
  doPreparedQuery($st, "Unable to identify domain");
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  $pagehead = "Manage User";
  if (!empty($rows)) {
    $pagehead = "Manage Team";
    foreach ($rows as $row) {
      $users[$row['id']] = $row['name'];
      if ($namechange && ($row['email'] === $_SESSION['email'])) {
        $key = $row['id'];
      }
    }
    $usercount = count($users);
    setExtent($usercount);
    if ($usercount === 1 || $namechange) {
      $key = $namechange ? $key : $row['id'];
      //$usercount = 1;
      $location = "./?edit=$key";
      if (!empty($error)) {
        $location .= "&error=$error";
      } else if ($namechange) {
        $location .= "&namechange";
      }
      header("Location: $location");
      exit;
    }
  } else {
    header("Location: ./?edit=$key");
    exit;
  }
  //load users
  return [$users, $selected, $pagehead, $pagetitle];
}

function updateUserDetails($id, $client_id, $assoc)
{
  include CONNECT;

  //$assoc = $domain && !$client_id ? false : $assoc;
  $sql = "UPDATE user SET name=:name, email=:email";
  $sql .= $assoc ? ", client_id=:cid" : '';
  $sql .= " WHERE id=:id";
  $st = $pdo->prepare($sql);
  /*
  if admin fails to assign a new domain to a user then obtain the client_id from the domain and reassign rather than have a client_id of null while an email domain points to a client.
  "A Contractor Scenario would be where the user has no client_id but shares the domain, but then they would not be deleted if a client were removed creating redundancy in the database; avoid"
  */
  if ($assoc) {
    $st->bindValue(":cid", nullify($client_id));
  }
  $st->bindValue(":name", $_POST['name']);
  $st->bindValue(":email", $_POST['email']);
  $st->bindValue(":id", $id);
  doPreparedQuery($st, 'Error setting user details');
}

//domain would change if updating client, but not the users email
function updateUserDomain($old, $new, $id = 0)
{
  if (($old && $new) && ($old !== $new)) {
    include CONNECT;
    $concat = replaceStrPos($new);
    //update email of employees IF the domain of client changes
    $sql = "UPDATE user SET email = $concat WHERE email LIKE '%$old'";
    //but restrict to a specific employee (eg leaving)
    if ($id) {
      $sql .= " AND id=$id";
    }
    doQuery($pdo, $sql, '');
  }
}

function fetchAllRoles($pdo, $keys = [], $selectedRoles = [])
{
  //Build the list of all roles
  $st = doQuery($pdo, "SELECT id, description FROM role", 'Error fetching list of roles.');
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  if ($keys !== []) {
    $rows = reAssoc($rows, $keys, 'id', 'description', [], 0, 0);
  }
  foreach ($rows as $row) {
    $roles[] = array('id' => $row['id'], 'description' => $row['description'], 'selected' => in_array($row['id'], $selectedRoles));
  }
  return $roles;
}

function resetRoles($role, $roles, $id)
{
  if (isQualified($role)) {
    include CONNECT;
    foreach ($roles as $role) {
      $st = $pdo->prepare("INSERT INTO userrole SET userid=:id, roleid=:rol");
      $st->bindValue(":id", $id);
      $st->bindValue(":rol", $role);
      doPreparedQuery($st, '<p>Error assigning selected role to user.</p>');
    } //end foreach
    return $roles;
  }
}

function getCurrentRole($id)
{
  include CONNECT;
  $st = $pdo->prepare("SELECT roleid FROM userrole WHERE userid=:id");
  $st->bindValue(":id", $id);
  doPreparedQuery($st, 'Error obtaining role id.');
  return $st->fetch(PDO::FETCH_ASSOC)['roleid'];
}

function verifyRole($old, $new)
{
  return ($old === $new) ? false : true;
}

function deleteRole($id, $role)
{
  if (isQualified($role)) {
    include CONNECT;
    $role = getCurrentRole($id);
    $sql = "DELETE FROM userrole WHERE userid=:id";
    $st = $pdo->prepare($sql);
    $st->bindValue(":id", $id);
    doPreparedQuery($st, 'Error removing obsolete user role entries.');
  }
}
//maybe require password of client to delete??
function deleteAlready($id)
{
  include CONNECT;
  $st = $pdo->prepare("DELETE FROM user WHERE id =:id");
  $st->bindValue(':id', $id);
  doPreparedQuery($st, 'Error deleting user.');
  header('Location: . ');
  exit();
}

function updatePassword($pdo, $password, $id)
{
  $password = md5($password . 'uploads');
  $st = $pdo->prepare("UPDATE user SET password =:pwd  WHERE id =:id");
  $st->bindValue(':pwd', $password);
  $st->bindValue(':id', $id);
  return doPreparedQuery($st, 'Error setting user password.');
}

function refreshDomain($priv, $posted)
{
  if ($priv === 'Admin') {
    return function ($postdom, $dbdom) use ($posted) {
      //in the process of disassociating ie currently belong to a client check you've entered a new domain;
      $id = $posted['id'];
      $relocate = null;
      if ($postdom === $dbdom) {
        $setcookie = doSetCookie(true);
        $setcookie('username', $posted['name']);
        $setcookie('email', $posted['email']);
        $relocate = "Location: ./?domainassoc=$id";
      }
      return [$postdom, $dbdom, 'assoc', $relocate];
    };
  } else {
    return function ($postdom, $dbdom) {
      return [$postdom, $dbdom, '', null];
    };
  }
}

if (!userIsLoggedIn()) {
  $pagetitle = "Log In";
  include TEMPLATE . 'login.html.php';
  exit();
}
/*$lefty not used just kept for ref
$lefty = "SELECT user.id, LEFT(user.email, LOCATE('@', user.email) -1) AS name FROM user WHERE id=:id";
*/
//$super = "andrewsykes@btinternet.com";
$prompt = NULL;
$users = [];
$error = query();
$pagehead = "Edit details";
$message = $error ?? '';
$denied = false;
$usercount = 0;
setExtent(0);
$selected = null;
$goto = '.';
$pageid = 'admin_user';
$calltext = "Add New User";
$callroute = 'add';
$nwp_id = $_GET['edit'] ?? NULL;
$nwproleplay = obtainUserRole();
$pagehead_role = $nwproleplay && !obtainUserRole(true);
$predicates = [partial('preg_match', '/^nwp/')];

$nwpagency = NULL;
$nwproleorder = ['Browser', 'Manager', 'Client', 'Client Admin', 'Admin'];

if (!$nwproleplay || $pagehead_role) {
  $e = 'Only Account Administrators may access this page!';
  $pagetitle = "Access Denied";
  //a manager role would be allowed to visit the landing page so redirect to there ../
  header("Location: ../?loginerror=$e");
  exit();
}
list($key, $priv) = $nwproleplay;
list($nwp_echange, $nwp_editor, $nwp_domain, $nwp_agency) = canEdit($nwp_id, '', $priv);
$pagetitle = preg_match("/client/i", $priv) ? "Admin" : "Admin | Edit Users";
//end of initial globals
$nwpadmin = isApproved($priv, 'ADMIN');

if (isset($_GET['domain'])) {
  //set by client/index.php: updates the second and top level domains of the users email address
  updateUserDomain($_GET['domain'], $_GET['updated']);
}
if (isset($_GET['add'])) {
  include CONNECT;
  $route = "Add";
  $action = 'addform';
  $button = 'Add User';
  $pagehead = "New User";
  $legend = null;
  $override = '';

  if (!isApproved($priv, 'admin')) {
    header("Location: ./?addno");
    exit();
  }
  $roles = fetchAllRoles($pdo, $nwproleorder);
  if ($nwpadmin) {
    $rows = doQuery($pdo, "SELECT * FROM client", "");
    foreach ($rows as $row) {
      $clientlist[$row['id']] = $row['name'];
    }
  }
  if (isApproved($priv, 'Client Admin') && !$nwpadmin) {
    unset($clientlist);
    $nwpst = $pdo->prepare(queryClient('email'));
    $nwpst->bindValue(":aux", $_SESSION['email']);
    doPreparedQuery($st, "Error fetching client details");
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $employer = nullify($row['employer']);
    $email = $row['email'];
    $roles = safeFilter($roles, function ($role) {
      return $role['id'] !== 'Admin';
    });
  }
  $admin = $nwpadmin;

  include 'form.html.php';
  exit();
} //////////////END OF ASSIGN

if (isset($_POST['action']) && $_POST['action'] === 'Add') {
  include CONNECT;
  //client_id: the only empty value MUST BE NULL, not empty string or zero
  $employerid = empty($_POST['employer']) ? NULL : $_POST['employer'];
  $clientadmin = preg_match("/admin/i", $priv) && preg_match("/client/i", $priv);
  $essentials = [$_POST['name'], $_POST['email'], $_POST['password']];
  $essentials = array_filter($essentials, function ($item) {
    return $item;
  });

  if (count($essentials) < 3) {
    header("Location: ./?addnotice");
    exit();
  }
  list($echange, $editor, $domain, $agency) = canEdit($nwp_id, $_POST['email'], $priv);

  $nwpst = $pdo->prepare("INSERT INTO user (name, email, password, client_id) VALUES(:nom, :e,:pwd, :clientid)");
  list($domchange, $comchange, $dom, $com) = queryEmail($editor, $_POST);
  $clientid = likeDomain(true, $dom);
  $employerid = $employerid ?? $clientid;
  $edom = "$dom.$com";
  $nwpst->bindValue(':nom', $_POST['name']);
  $nwpst->bindValue(':e', $_POST['email']);
  $nwpst->bindValue(':pwd', $_POST['password']);
  $nwpst->bindValue(':clientid', nullify($employerid));
  $res = doPreparedQuery($nwpst, 'Error adding user');
  $aid = lastInsert($pdo, DBSYSTEM, 'user');

  if (isset($_POST['password']) && $_POST['password'] != '') {
    $res = updatePassword($pdo, $_POST['password'], $aid);
  }
  $roles = isset($_POST['roles']) ? $_POST['roles'] : [];
  if ($employerid) {
    $id = nullify($employerid);
    $nwpst = doQuery($pdo, "SELECT domain FROM client WHERE id=$id", "Error retrieving clients from database, oops");
    $nwprow = $st->fetch(PDO::FETCH_ASSOC);
    $dbdom = $nwprow['domain'];

    $nwpst = $pdo->prepare("SELECT email FROM user WHERE client_id=:cid AND id=:aid");
    $nwpst->bindValue(':aid', $aid);
    $nwpst->bindValue(':cid', intval($employerid));
    doPreparedQuery($nwpst, 'Error fetching domain.');
    $nwprow = $st->fetch(PDO::FETCH_ASSOC);
    list($dom, $com) = parseEmail($nwprow['email']);
    updateUserDomain("$dom.$com", $dbdom, $aid);
  }
  resetRoles($priv, $roles, $aid);
  header('Location: .');
  exit();
} //end of addform

//exits
if (isset($_POST['confirm'])) {
  $location = " .";

  if ($_POST['confirm'] == 'Yes') {
    include CONNECT;
    $role = null;
    $roles = [];
    $clientadmin = isApproved($priv, 'admin');
    list($echange, $editor, $domain, $agency) = canEdit($_POST['id'], '', $priv);
    $crud = ($agency || $editor);
    //editor or freelance
    if (!$domain) {
      if ($nwpadmin && $editor) {
        header("Location: ./?self"); //delegate deleting an admin to a peer
        exit();
      } else if ($crud) {
        deleteAlready($_POST['id']);
        header("Location: ./?logout");
        exit();
      }
    }
    $sql = "SELECT user.id FROM user INNER JOIN client ON user.client_id = client.id WHERE client.domain='$domain'";
    $st = doQuery($pdo, $sql, 'Error fetching client.');
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) === 1) {
      $location = $editor ? "Location: ./?deniedbyclient" : "Location: ./?lastuser";
      header($location);
      exit();
    }

    $rolesql = "SELECT role.id AS role, userrole.userid AS id FROM role LEFT JOIN userrole ON role.id = userrole.roleid WHERE userrole.userid=:id";
    $nwpst = $pdo->prepare($rolesql);

    if (!empty($rows)) {
      foreach ($rows as $r) {
        $nwpst->bindValue(':id',  $r['id']);
        doPreparedQuery($st, 'Error fetching roles.');
        $nwpro = $st->fetch(PDO::FETCH_ASSOC);
        $roles[$ro['id']] = $nwpro['role'];
      }
    }
    /*
    only Admin or Client Admin roles SHOULD get to this point
    but a "Employer" could have more than one "Client Admin" roles
    if removing one 
    */
    $roles = safeFilter($roles, function ($role) {
      return preg_match("/admin/i", $role);
    });

    $danger = count($roles) < 2 && $editor;
    if (!$danger) {
      deleteAlready($_POST['id']);
    } else {
      $deny = $nwpadmin ? '/?deniedbyadmin' : '/?deniedbyclient';
      $location .= $deny;
    }
  }
  header("Location: $location");
  exit();
} //confirm

if (isset($_GET['delete'])) {
  $id = $_GET['delete'];
  $title = "Prompt";
  $prompt = "Are you sure you want to delete this user?";
  $call = "confirm";
  $pos = "Yes";
  $neg = "No";
  $action = '';
  $formname = 'deleteuserform';
  $template = 'confirm.html.php';
  $crud = $nwp_editor || $nwp_agency;
  if (!$crud) {
    header("Location: ./?denied");
  }
}

if (isset($_POST['change']) || isset($_GET['cancel'])) {
  if ($_POST['change'] == 'Yes') {
    $id = $_POST['id'];
    //header("Location: ../?action=logout");
    header("Location: ./?edit=$id");
    exit();
  } else {
    unsetDetails();
  }
}

if (isset($_POST['action']) && $_POST['action'] === 'Edit') {
  include CONNECT;
  $action = "editform";
  $override = $_POST['override'];
  $id = $_POST['id'];
  $roles = $_POST['roles'] ?? [];
  $nwpold = null;
  $nwpnew = null;
  $nwprole = null;
  $nwprolechange = null;

  $location = 'Location: .';
  $nwprelocate = "Location: ./?domainflag=$id";
  list($nwpechange, $editor, $nwpdomain, $nwpagency, $name) = canEdit($id, $_POST['email'], $priv);
  list($nwpdomfail, $nwppostdom, $nwpdomchange, $nwpemployerid) = verifyDom($editor, $nwpadmin, $nwpdomain, nullify($_POST['employer']));

  if (!$override && ($editor && $nwpechange && !$nwpdomfail)) {
    $title = "Prompt";
    $prompt = "Changing your email will log you out of the current session. Proceed?";
    $call = "change";
    $pos = "Yes";
    $neg = "No";
    $action = './?logout';
    $formname = 'changedetailsform';
    $template = 'confirm.html.php';
    $setcookie = doSetCookie(true);
    $setcookie('username', $_POST['name']);
    $nwpswitch = false;
    if (!$nwpdomchange) {
      $setcookie('email', $_POST['email']);
    } else {
      $prompt = "Only the database administrator is permitted to amend the email domain. You may amend the local-part, and your username. Proceed?";
    }
  }
  /*
  admin on client
  1 assign user to client : update domain AND add client_id assoc !$dom
  2 assign client to new client update domain AND update client_id assoc
  3 unassign from client : input fresh domain AND nullify client_id assoc
  BUT
  2 and 3: would potentialy leave a client with no users which should not be allowed
  we could have a last user warning, but it is better to delete the client and then assign
  to a new client
  */

  if (!isset($prompt)) {
    //no domchange but potential employerid
    if (!$nwpdomfail) {
      $nwprelocate = null;
      $nwpassoc = true;
      $nwp = isEmployer($_POST, 'employer');
      list($_, $nwpemployerdom) = $nwp();
      //ADMIN ONLY empty $nwpemployerdom then we are unassigning user from client, make sure you provide their new domain
      if (!$nwpemployerdom && !$override) {
        $nwp = isEmployer($_POST, 'id');
        list($clientid, $nwpdomain) = $nwp();
        $nwp = refreshDomain($priv, $_POST);
        list($nwppostdom, $nwpdomain, $nwpassoc, $nwprelocate) = $nwp($nwppostdom, $nwpdomain);
      } else if (!$nwpdomain && $nwpemployerdom) {
        $nwpnew = $nwpemployerdom;
        $nwpold = $nwppostdom;
      } else if ($nwpdomain !== $nwpemployerdom) {
        $nwpnew = $nwpemployerdom;
        $nwpold = $nwpdomain;
      }
    }
    if (isset($nwprelocate)) {
      header($nwprelocate);
      exit();
    }

    if ($editor || $nwpagency) {
      if (isset($_POST['password']) && $_POST['password'] != '') {
        if ($override) {
          $res = updatePassword($pdo, $_POST['password'], $id);
        } else {
          header("Location: ./?pwd=$id");
          exit();
        }
      }

      updateUserDetails($id, $nwpemployerid, $nwpassoc);
      updateUserDomain($nwpold, $nwpnew, $id);

      if ($nwpagency) {
        $nwprole = getCurrentRole($id);
        deleteRole($id, $priv);
        resetRoles($priv, $roles, $id);
        $nwprolechange = verifyRole($nwprole, getCurrentRole($id));
        //$clientid is allowed to be NULL (not any other empty) if a user wants to disassociate from a client
      }
      if ($editor) {
        if ($nwpechange || ($nwprolechange && $editor)) {
          header("Location: ../?action=logout");
          exit();
        }
      }
    }

    if ($name && $name !== $_POST['name']) {
      $location .= "/?namechange=$id";
    }
    header($location);
    exit();
  } //if !prompt
} ///END OF editform //////


//////
//directly load form.html.php if only one user/client
if (checkIsset($_GET, ['edit', 'pwd', 'domainflag', 'domainassoc'])) {
  $override = NULL;
  $pwdID = $_GET['pwd'] ?? NULL;
  $domainflagID = $_GET['domainflag'] ?? NULL;
  $domainassocID = $_GET['domainassoc'] ?? NULL;
  $flagID = $pwdID ?? $domainflagID ?? $domainassocID ?? NULL;
  $namechange = $_GET['namechange'] ?? NULL;
  $nwpclientrow = null;
  $legend = isset($_GET['namechange']) ? 'Name succesfully changed' : NULL;
  $class = '';
  $nwpClient = preg_match('/admin/i', $priv) && preg_match('/client/i', $priv);
  $message = $_GET['error'] ?? '';
  $id = isset($_GET['edit']) ? $_GET['edit'] : $flagID;
  $id =  $id ?? $flagid ?? NULL;

  $calltext = "Delete User";
  $callroute = "delete=$id";

  $warning = 'You do not have sufficient privileges to edit this users details.';
  list($nwpechange, $editor, $nwpdomain, $nwpagency) = canEdit($id, $_POST['email'] ?? '', $priv);
  //DON'T FORGET WE CAN ARRIVE HERE DIRECT FROM A LINK AND NOT FROM A REDIRECT FROM EDITING
  if (isset($_GET['error'])) {
    unset($calltext);
    unset($callroute);
  }

  if (!$nwp_agency) {
    if ($editor || $nwpagency) {
      $warning = '';
    } else {
      $message = $warning;
    }
  }
  include CONNECT;
  $nwpst = $pdo->prepare("SELECT id, name, email FROM user WHERE id =:id");
  $nwpst->bindValue(":id", $id);
  doPreparedQuery($nwpst, "Error fetching user details");
  $nwprow = $nwpst->fetch(PDO::FETCH_ASSOC);

  if (!$message) { //either editor or admin
    $warning = 'Polite Notice: changing an email or password will automatically log you out.';
    $message = ($pwdID && $editor) ? $warning : ''; //alert if yourself
    $message = $message ? $message : ($domainflagID ? 'You do not have sufficient privileges to change the domain name. Please contact the database administrator.' : '');
    $message = $message ? $message : ($domainassocID ? 'You must assign a new domain if you are unassigning from the current client' : '');

    if ($message && ($message === $warning)) {
      $message .= ' You can proceed now that the form is in override mode but you will need to log in again with your updated details.';
    }
  }
  if ($flagID) {
    $nwpst = $pdo->prepare(queryClient('id'));
    $nwpst->bindValue(":aux", $flagID);
    //this may fail if user is not a client, so $nwpclientrow is conditional
    doPreparedQuery($nwpst, "Error fetching user details.");
    $nwpclientrow = $nwpst->fetch(PDO::FETCH_ASSOC);
  }
  //make sure not to overrwrite the $nwprow variable
  $nwprow = $nwpclientrow ? $nwpclientrow : $nwprow;
  $route = "Edit";
  $pagehead = 'Edit User';
  $action = 'editform';
  $button = 'Update User';
  $employer = NULL;
  $roles = [];
  $clientlist = [];
  $selectedRoles = [];
  $id = $nwprow['id'];
  $name = isset($_COOKIE['username']) ? $_COOKIE['username'] : $nwprow['name'];
  $email = isset($_COOKIE['email']) ? $_COOKIE['email'] : $nwprow['email'];
  $override = $pwdID;
  $override = $override ?? $_COOKIE['username'] ?? NULL;
  $override = $override ?? $_COOKIE['email'] ?? NULL;
  $override = $override ?? $flagID ?? NULL;
  unsetDetails();

  $class = $override ? 'details override' : 'details';
  $legend = isset($legend) ? $legend : ($override && !$message ? "You may now proceed with your edits and submit the form." : '');

  //prep roles...
  if (preg_match('/admin/i', $priv)) {
    $nwpst = $pdo->prepare("SELECT roleid FROM userrole WHERE userid=:id");
    $nwpst->bindValue(":id", $id);
    doPreparedQuery($nwpst, "<p>Error fetching list of assigned roles.</p>");
    $nwprows = $nwpst->fetchAll(PDO::FETCH_ASSOC);
    foreach ($nwprows as $nwp_row) {
      $selectedRoles[] = $nwp_row['roleid'];
    }
    $roles = fetchAllRoles($pdo, $nwproleorder, $selectedRoles);
  }

  if ($nwpadmin) {
    $nwpst = doQuery($pdo, "SELECT id, name FROM client ORDER BY name", 'Error retrieving clients from database!');
    $nwprows = $nwpst->fetchAll(PDO::FETCH_ASSOC);
    foreach ($nwprows as $nwp_row) {
      $clientlist[$nwp_row['id']] = $nwp_row['name'];
    }
    if (!isset($nwprow['employer'])) {
      $nwpst = $pdo->prepare("SELECT client_id AS employer FROM user WHERE id=:id");
      $nwpst->bindValue(":id", $id);
      doPreparedQuery($nwpst, "Error retrieving client id from user!");
      $nwprow = $nwpst->fetch(PDO::FETCH_ASSOC);
    }
    //selects client in drop down menu UNLESS you have the warning about assigning a new domain
    $employer =  $domainassocID ? NULL : $nwprow['employer'];
  }

  if ($nwpClient) {
    $roles = safeFilter($roles, function ($role) {
      return $role['id'] !== 'Admin';
    });
  }
  $admin = $nwpadmin;
  include 'form.html.php';
  exit();
} //get_edit

//\\\\\\|/////////\\\\\\|/////////\\\\\\|/////////\\\\\\|/////////\\\\\\|/////////\\\\\\|///////
$nwpsql = "SELECT user.id, user.name FROM user LEFT JOIN client ON user.client_id=client.id WHERE client.domain IS NULL"; //USING ID NOT DOMAIN
$admin = isApproved($priv, 'ADMIN');

if (isset($_POST['user'])) { //dropdown
  if ($_POST['user'] === '') {
    header("Location: ./?selectuser");
    exit();
  }
  list($users, $selected, $pagehead, $pagetitle) = filterUsers($_POST['user'], $pagetitle);
}
//on landing try client; a single client will redirect to form.html.php, a multi team client will prepare variables for users.html.php
if ($users === []) {
  include CONNECT;
  $nwpst = $pdo->prepare(queryClient('id'));
  $nwpst->bindValue(":aux", $key);
  doPreparedQuery($nwpst, '');
  $nwprow = $nwpst->fetch(PDO::FETCH_ASSOC);
  if (isset($nwprow['domain']) && !isset($prompt)) {
    list($users, $selected, $pagehead, $pagetitle) = filterUsers($nwprow['domain'], $pagetitle, $error);
    setExtent(count($users));
  }
}

//if $prompt is set and we have a one member client this will yield an empty set
if ($users === []) {
  include CONNECT;
  if (!$admin) {
    $nwpsql .= " AND user.id=$key";
  }
  $nwpsql .= " ORDER BY name";
  $nwpst = doQuery($pdo, $nwpsql, '');
  $nwprows = $nwpst->fetchAll(PDO::FETCH_ASSOC);
  foreach ($nwprows as $nwprow) {
    $users[$nwprow['id']] = $nwprow['name'];
  }
}

//prepare list
if ($admin) {
  include CONNECT;
  $nwpres = doQuery($pdo, "SELECT client.domain, client.name FROM client ORDER BY name", 'Database error fetching clients:');
  $nwprows = $nwpres->fetchAll();
  foreach ($nwprows as $nwprow) {
    $client[$nwprow['domain']] = $nwprow['name'];
  }
}

$message = $message ? $message : $error;
$usercount = isApproved($priv, 'ADMIN') ? 2 : count($users); //2 ie more than 1
//setExtent is largely used for displaying conditional content, appropriate buttons etc..
setExtent($usercount);

if ($usercount === 1 && !isset($prompt)) {
  $calltext = "Delete User";
  $callroute = "delete=$key";
  $location = "./?edit=$key";
  if (!empty($error)) {
    unset($callroute);
    unset($calltext);
    $location .= "&error=$error";
  }
  if (isset($_GET['namechange'])) {
    $location .= "&namechange";
  }
  header("Location: $location"); //GO DIRECT TO EDIT FORM, unless...
  exit();
} else { //usercount is zero or more than one
  //...clients of one in number can only end up here if a prompt is set and usercount is zero
  include 'users.html.php';
}
