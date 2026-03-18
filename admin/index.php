<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/access.inc.php';


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
  $domainstr = "RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))";
  $sql = "SELECT client.id AS employer, domain, email FROM client LEFT JOIN user ON $domainstr = client.domain";
  $w = " WHERE user.email=:aux";

  $like = "SELECT client.id AS employer, domain, name FROM client WHERE client.domain LIKE '$mixed%'";

  if (is_bool($mixed)) {
    return $mixed ? $sql . $w : $sql;
  } else {
    return $like;
  }
}

//object and prop or; no prop object MUST be a domain
function isEmployer($o, $p = '')
{
  $sql = queryClient($p === 'email');
  $id = null;
  //https://stackoverflow.com/questions/2628138/how-to-select-domain-name-from-email-address
  if (!$p) {
    $sql = queryClient($o);
  }
  if ($p === 'email') {
    $sql = queryClient(true);
    $id = $o[$p] ?? 0;
  }
  if ($p === 'id') {
    $sql .= "  WHERE user.id=:aux";
    $id = $o[$p] ?? 0;
  }
  if ($p === 'employer') {
    $id = $o[$p] ?? 0;
    $sql = "SELECT client.id, domain FROM client WHERE client.id =:aux";
  }

  return function () use ($id, $sql) {
    include CONNECT;
    $st = $pdo->prepare($sql);
    if (isset($id)) {
      $st->bindValue(':aux',  $id);
    }
    doPreparedQuery($st, 'Error fetching client.');
    return $st->fetch(PDO::FETCH_NUM);
  };
}

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

function isFreelancer($pdo, $id)
{
  $sql = "SELECT id, email FROM user WHERE client_id IS NULL AND id=:id";
  $st = $pdo->prepare($sql);
  $st->bindValue(':id',  $id);
  doPreparedQuery($st, 'Error fetching client.');
  $row = $st->fetch(PDO::FETCH_ASSOC);
  return $row['id'];
}

function hasDomain($key)
{
  include CONNECT;
  $st = $pdo->prepare("SELECT domain FROM client WHERE domain=:domain");
  $st->bindValue(":domain", $key);
  doPreparedQuery($st, "Unable to identify domain");
  return $st->fetch(PDO::FETCH_ASSOC);
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
  $clientFunc = function ($change, $arg) {
    return $change && $arg;
  };
  $adminFunc = function ($change, $editor) {
    return $change ? $editor : false;
  };

  $clientcb = $admin ? curry2($clientFunc)($employerid) : 'identity';
  $admincb = curry2($adminFunc)($editor);
  $usercb = curry2('likeDomain')($dom);
  $actions = ['client' => $clientcb, 'admin' => $admincb, 'user' => $usercb];
  $k = searchGroup(true, [$domain, $admin, true], ['client', 'admin', 'user']);
  $validateDom = $actions[$k];
  //returned curried function expects a $change boolean: $domchange || $comchange
  $domfail = $validateDom($domchange || $comchange);
  return [$domfail, "$dom.$com"];
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
  $pagehead = "foo";
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
function defaultQuery($key, $priv)
{
  if (preg_match("/client/i", $priv)) {
    return "SELECT id, name FROM user where id = $key ORDER BY name";
  }
  return "SELECT id, name FROM user ";
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

function fetchSelectedRoles($pdo, $id, $keys = [])
{
  $st = $pdo->prepare("SELECT roleid FROM userrole WHERE userid=:id");
  $st->bindValue(":id", $id);
  doPreparedQuery($st, "<p>Error fetching list of assigned roles.</p>");
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $row) {
    $selectedRoles[] = $row['roleid'];
  }
  return fetchAllRoles($pdo, $keys, $selectedRoles);
}

function resetRoles($role, $roles, $id)
{
  if (isQualified($role)) {
    include CONNECT;
    foreach ($roles as $role) {
      $sql = "INSERT INTO userrole SET userid=:id, roleid=:rol";
      $st = $pdo->prepare($sql);
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
  $sql = "SELECT roleid FROM userrole WHERE userid=:id";
  $st = $pdo->prepare($sql);
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
  $sql = "UPDATE user SET password =:pwd  WHERE id =:id";
  $st = $pdo->prepare($sql);
  $st->bindValue(':pwd', $password);
  $st->bindValue(':id', $id);
  return doPreparedQuery($st, 'Error setting user password.');
}

if (!userIsLoggedIn()) {
  $pagetitle = "Log In";
  include TEMPLATE . 'login.html.php';
  exit();
}
$lefty = "SELECT user.id, LEFT(user.email, LOCATE('@', user.email) -1) AS name FROM user WHERE id=:id";
$super = "andrewsykes@btinternet.com";
$prompt = NULL;
$users = [];
$id = $_GET['edit'] ?? '';
$error = query();
$pagehead = "Edit details";
$message = $error ?? '';
$denied = false;
$usercount = 0;
setExtent(0);
$selected = null;
$goto = '.';
$pageid = 'admin_user';
$roleorder = ['Browser', 'Manager', 'Client', 'Client Admin', 'Admin'];
$calltext = "Add New User";
$callroute = 'add';

$formvars = ['pagetitle', 'message', 'name', 'email', 'job', 'roles', 'id', 'route', 'override', 'button', 'priv'];
$uservars = ['manage', 'priv', 'client', 'users'];
$domainstr = "RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))";
$is_client_sql = "SELECT client.id AS employer, domain, email FROM client LEFT JOIN user ON $domainstr = client.domain WHERE user.email=:email";

$isContractor = function ($email, $clientid = NULL) {
  include CONNECT;
  $sql = queryClient(true);
  $st = $pdo->prepare($sql);
  $st->bindValue(':aux', $email);
  doPreparedQuery($st, 'Error querying client credentials');
  $row = $st->fetch(PDO::FETCH_ASSOC);
  return (isset($row['employer']) && is_null($clientid)) ? $row['employer'] : $clientid;
};

$agency = NULL;
$lastuser = $_GET['lastuser'] ?? NULL;

$roleplay = obtainUserRole();
$pagehead_role = $roleplay && !obtainUserRole(true);

if (!$roleplay || $pagehead_role) {
  $e = 'Only Account Administrators may access this page!';
  $pagetitle = "Access Denied";
  //a manager role would be allowed to visit the landing page so redirect to there ../
  header("Location: ../?loginerror=$e");
  exit();
}
list($key, $priv) = $roleplay;
list($_echange, $_editor, $_domain, $_agency) = canEdit($id, '', $priv);
$pagetitle = preg_match("/client/i", $priv) ? "Admin" : "Admin | Edit Users";

//end of initial globals
if (isset($_GET['domain'])) {
  updateUserDomain($_GET['domain'], $_GET['updated']);
}
if (isset($_GET['add'])) {
  include CONNECT;
  $route = "Add";
  $action = 'addform';
  $button = 'Add User';
  $legend = null;
  $override = '';
  $crud = isApproved($priv, 'admin');
  $admin = isApproved($priv, 'ADMIN');
  $clientadmin = isApproved($priv, 'Client Admin');
  if (!$crud) {
    header("Location: ./?addno");
    exit();
  }
  $roles = fetchAllRoles($pdo, $roleorder);
  if ($admin) {
    $rows = doQuery($pdo, "SELECT * FROM client", "");
    foreach ($rows as $row) {
      $clientlist[$row['id']] = $row['name'];
    }
  }
  if ($clientadmin && !$admin) {
    unset($clientlist);
    $st = $pdo->prepare($is_client_sql);
    $st->bindValue(":email", $_SESSION['email']);
    doPreparedQuery($st, "Error fetching client details");
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $employer = empty($row) ? NULL : $row['employer'];
    $email = $row['domain'];

    $roles = safeFilter($roles, function ($role) {
      return $role['id'] !== 'Admin';
    });
  }
  $pagehead = "New User";
  include 'form.html.php';
  exit();
} //////////////END OF ASSIGN

if (isset($_POST['action']) && $_POST['action'] === 'Add') {
  include CONNECT;
  //client_id: the only empty value MUST BE NULL, not empty string or zero
  $employerid = empty($_POST['employer']) ? NULL : $_POST['employer'];
  $clientadmin = preg_match("/admin/i", $priv) && preg_match("/client/i", $priv);
  $admin = isApproved($priv, 'ADMIN');
  $essentials = [$_POST['name'], $_POST['email'], $_POST['password']];
  $essentials = array_filter($essentials, function ($item) {
    return $item;
  });

  if (count($essentials) < 3) {
    header("Location: ./?addnotice");
    exit();
  }
  list($echange, $editor, $domain, $agency) = canEdit($id, $_POST['email'], $priv);

  $sql = "INSERT INTO user (name, email, password, client_id) VALUES(:nom, :e,:pwd, :clientid)";
  $st = $pdo->prepare($sql);
  /*
  applies to admin users only as $_POST['employer'] is set by default by client
  if the email field corresponds to an existing client AND there is no selection in the assign to client drop down $_POST['employer'] then we can correct with the isContractor function
  if we have a client id provided by $_POST['employer'] we must check that the supplied email DOMAIN matches
  Potentially there may be a case for having a contractor role but it would have to be deleted if the client was removed from the database and without a clientid this would not happen through the referential integrity enforced by the database. But an additional check could be made at this point, in the meantime the "contractor" would be free to leave the role
  */

  list($domchange, $comchange, $dom, $com) = queryEmail($editor, $_POST);

  $clientid = likeDomain(true, $dom);
  $employerid = $employerid ?? $clientid;
  $edom = "$dom.$com";
  $st->bindValue(':nom', $_POST['name']);
  $st->bindValue(':e', $_POST['email']);
  $st->bindValue(':pwd', $_POST['password']);
  $st->bindValue(':clientid', $employerid);
  $res = doPreparedQuery($st, 'Error adding user');
  $aid = lastInsert($pdo);


  if (isset($_POST['password']) && $_POST['password'] != '') {
    $res = updatePassword($pdo, $_POST['password'], $aid);
  }
  $roles = isset($_POST['roles']) ? $_POST['roles'] : [];

  if ($employerid) {
    $id = $employerid ?? $contractorId;
    $st = doQuery($pdo, "SELECT domain FROM client WHERE id=$id", "Error retrieving clients from database, oops");
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $truedom = $row['domain'];

    $sql = "SELECT $domainstr AS dom FROM user WHERE client_id=:cid AND id=:aid";
    $st = $pdo->prepare($sql);
    $st->bindValue(':aid', $aid);
    $st->bindValue(':cid', intval($employerid));
    doPreparedQuery($st, 'Error fetching domain.');
    $row = $st->fetch(PDO::FETCH_ASSOC);
    updateUserDomain($row['dom'], $truedom, $aid);
    /*
    if ($contractorId) { //required?
      doQuery($pdo, "UPDATE user INNER JOIN client ON $domainstr = client.domain SET client_id=$contractorId WHERE $domainstr = client.domain", "Error updating client");
    }
      */
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
    $id = $_POST['id'];
    $role = null;
    $roles = [];
    $admin = isApproved($priv, 'ADMIN');
    $clientadmin = isApproved($priv, 'admin');
    list($echange, $editor, $domain, $agency) = canEdit($id, '', $priv);
    $crud = ($agency || $editor);
    //editor or freelance
    if (!$domain) {
      if ($admin && $editor) {
        header("Location: ./?self"); //delegate deleting an admin to a peer
        exit();
      } else if ($crud) {
        deleteAlready($id);
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
    $st = $pdo->prepare($rolesql);

    if (!empty($rows)) {
      foreach ($rows as $r) {
        $st->bindValue(':id',  $r['id']);
        doPreparedQuery($st, 'Error fetching roles.');
        $ro = $st->fetch(PDO::FETCH_ASSOC);
        $roles[$ro['id']] = $ro['role'];
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
      deleteAlready($id);
    } else {
      $deny = $admin ? '/?deniedbyadmin' : '/?deniedbyclient';
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
  //$action = '';
  $formname = 'deleteuserform';
  $template = 'confirm.html.php';
  $crud = $_editor || $_agency;
  if (!$crud) {
    header("Location: ./?denied");
  }
}

if (isset($_POST['change']) || isset($_GET['cancel'])) {
  if ($_POST['change'] == 'Yes') {
    $id = $_POST['id'];
    header("Location: ./?edit=$id");
    exit();
  } else {
    unsetDetails();
  }
}

if (isset($_POST['action']) && $_POST['action'] === 'Edit') {
  include CONNECT;
  $local = [];
  $action = "editform";
  $override = $_POST['override'];
  $id = $_POST['id'];
  $roles = $_POST['roles'] ?? [];
  $admin = isApproved($priv, 'ADMIN');

  $role = null;
  $rolechange = null;

  $location = 'Location: .';
  $relocate = "Location: ./?domainflag=$id";
  list($echange, $editor, $domain, $agency, $name) = canEdit($id, $_POST['email'], $priv);

  list($domfail, $postdom) = verifyDom($editor, $admin, $domain, nullify($_POST['employer']));

  if (!$override && ($editor && $echange && !$domfail)) {
    $title = "Prompt";
    $prompt = "Changing your email will log you out of the current session. Proceed?";
    $call = "change";
    $pos = "Yes";
    $neg = "No";
    $action = './?editform';
    $formname = 'changedetailsform';
    $template = 'confirm.html.php';
    $setcookie = doSetCookie(true);
    $setcookie('username', $_POST['name']);
    if (!$domchange) {
      $setcookie('email', $_POST['email']);
    } else {
      $prompt = "Only the database administrator is permitted to amend the email domain. You may amend the local-part, and your username. Proceed?";
    }
  }

  if (!isset($prompt)) {
    //unsetDetails();
    if (!$domfail) {
      $relocate = null;
      $byClientID = isEmployer($_POST, 'employer');
      list($_, $dom) = $byClientID();
      if (!$dom) {
        //in the process of disassociating ie currently belong to a client check you've entered a new domain;
        if ($postdom === $domain) {
          $relocate = "Location: ./?domainassoc=$id";
          $setcookie = doSetCookie(true);
          $setcookie('username', $_POST['name']);
          $setcookie('email', $_POST['email']);
        } else {
          $dom = $postdom;
          $postdom = $domain;
          $domain = $dom;
        }
      } else if (!$domain) {
        $domain = $dom;
      }
    }
    if (isset($relocate)) {
      header($relocate);
      exit();
    }

    if ($editor || $agency) {
      include CONNECT;
      $sql = "UPDATE user SET name=:name, email=:email";
      $sql .= $assoc ? ", client_id=:cid" : '';
      $sql .= " WHERE id=:id";
      $st = $pdo->prepare($sql);
      $st->bindValue(":cid", $employerid);
      $st->bindValue(":name", $_POST['name']);
      $st->bindValue(":email", $_POST['email']);
      $st->bindValue(":id", $id);
      doPreparedQuery($st, 'Error setting user details');

      //check EXISTING email not $_POST
      if (isset($_POST['password']) && $_POST['password'] != '') {
        if ($override) {
          $res = updatePassword($pdo, $_POST['password'], $id);
        } else {
          header("Location: ./?pwd=$id");
          exit();
        }
      }

      $sql = "UPDATE user SET client_id=:cid WHERE id =:id";
      $st = $pdo->prepare($sql);
      $st->bindValue(":cid", $employerid);
      $st->bindValue(":id", $id);
      doPreparedQuery($st, 'Error updating client id');
      updateUserDomain($edom, $domain, $id);

      if ($agency) {
        $role = getCurrentRole($id);
        deleteRole($id, $priv);
        resetRoles($priv, $roles, $id);
        $rolechange = verifyRole($role, getCurrentRole($id));
        //$clientid is allowed to be NULL (not any other empty) if a user wants to disassociate from a client
      }
      if ($editor) {
        if ($echange || ($rolechange && $editor)) {
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
//note unset unneeded vars at this point??
} ///END OF editform //////


//////
//directly load form.html.php if only one user/client
if (checkIsset($_GET, ['edit', 'pwd', 'domainflag', 'domainassoc'])) {
  $domainflagID = $_GET['domainflag'] ?? NULL;
  $domainassocID = $_GET['domainassoc'] ?? NULL;
  $pwdID = $_GET['pwd'] ?? NULL;
  $namechange = $_GET['namechange'] ?? NULL;
  $clientrow = null;

  if (isset($_GET['namechange'])) {
    $legend = 'Name succesfully changed';
  }

  include CONNECT;
  $class = '';
  $admin = ($priv === 'Admin');
  $adminClient = preg_match('/admin/i', $priv) && preg_match('/client/i', $priv);
  $message = $_GET['error'] ?? '';
  $id = isset($_GET['edit']) ? $_GET['edit'] : ($pwdID ?? NULL);
  $id = !empty($id) ? $id : $domainflagID ?? $domainassocID ?? '';

  $calltext = "Delete User";
  $callroute = "delete=$id";

  $warning = 'You do not have sufficient privileges to edit this users details.';
  list($echange, $editor, $domain, $agency) = canEdit($id, $_POST['email'] ?? '', $priv);

  //DON'T FORGET WE CAN ARRIVE HERE DIRECT FROM A LINK AND NOT FROM A REDIRECT FROM EDITING
  if (isset($_GET['error'])) {
    unset($calltext);
    unset($callroute);
  }

  if (!$_agency) {
    if ($editor || $agency) {
      $warning = '';
    } else {
      $message = $warning;
    }
  }

  $st = $pdo->prepare("SELECT id, name, email, $domainstr AS dom FROM user WHERE id =:id");
  $st->bindValue(":id", $id);
  doPreparedQuery($st, "Error fetching user details");
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if (!$message) { //either editor or admin
    $warning = 'Polite Notice: changing an email or password will automatically log you out.';
    $message = ($pwdID && $editor) ? $warning : ''; //alert if yourself
    $message = $message ? $message : ($domainflagID ? 'You do not have sufficient privileges to change the domain name. Please contact the database administrator.' : '');
    $message = $message ? $message : ($domainassocID ? 'Please provide a new domain for this user' : '');

    if ($message && ($message === $warning)) {
      $message .= ' You can proceed now that the form is in override mode but you will need to log in again with your updated details.';
    }
  }

  if ($domainflagID || $domainassocID) {
    $sql = "SELECT user.id, user.name, user.email FROM client LEFT JOIN user ON $domainstr = client.domain WHERE user.id=:dom";
    $st = $pdo->prepare($sql);
    $st->bindValue(":dom", $domainflagID);
    //this may fail if user is not a client, so $clientrow is conditional
    doPreparedQuery($st, "Error fetching user details.");
    $clientrow = $st->fetch(PDO::FETCH_ASSOC);
  }

  $row = $clientrow ? $clientrow : $row;

  $route = "Edit";
  $pagehead = 'Edit User';
  $action = 'editform';
  $button = 'Update User';
  $roles = [];
  $clientlist = [];
  $selectedRoles = [];
  $id = $row['id'];
  $name = isset($_COOKIE['username']) ? $_COOKIE['username'] : $row['name'];
  $email = isset($_COOKIE['email']) ? $_COOKIE['email'] : $row['email'];
  unsetDetails();
  $override = $pwdID ?? NULL;
  $override = $override ?? $_COOKIE['username'] ?? NULL;
  $class = $override ? 'details override' : 'details';
  $legend = isset($legend) ? $legend : ($override ? "You may now proceed with your edits and submit the form." : '');

  //prep roles...
  if (preg_match('/admin/i', $priv)) {
    $st = $pdo->prepare("SELECT roleid FROM userrole WHERE userid=:id");
    $st->bindValue(":id", $id);
    doPreparedQuery($st, "<p>Error fetching list of assigned roles.</p>");
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
      $selectedRoles[] = $row['roleid'];
    }
    $roles = fetchAllRoles($pdo, $roleorder, $selectedRoles);
  }

  if ($admin) {
    $st = doQuery($pdo, "SELECT id, name FROM client ORDER BY name", 'Error retrieving clients from database!');
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
      $clientlist[$row['id']] = $row['name'];
    }
    $st = $pdo->prepare("SELECT client_id FROM user WHERE id=:id");
    $st->bindValue(":id", $id);
    doPreparedQuery($st, "Error retrieving client id from user!");
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $employer = $row['client_id']; //selects client in drop down menu
  }

  if ($adminClient) {
    $roles = safeFilter($roles, function ($role) {
      return $role['id'] !== 'Admin';
    });
  }
  include 'form.html.php';
  exit();
} //get_edit

//LANDING...
//$sql = defaultQuery($key, $priv);
//display users___________________________________________________________________

$sql = "SELECT user.id, user.name FROM user LEFT JOIN (SELECT user.name, client.domain FROM user INNER JOIN client ON $domainstr=client.domain) AS employer ON $domainstr=employer.domain WHERE employer.domain IS NULL"; //this overwrites above query to filter out users as employees
$sql = "SELECT user.id, user.name FROM user LEFT JOIN client ON user.client_id=client.id WHERE client.domain IS NULL"; //USING ID NOT DOMAIN
$admin = isApproved($priv, 'ADMIN');


if (isset($_POST['user'])) { //dropdown
  if ($_POST['user'] === '') {
    header("Location: ./?selectuser");
    exit();
  }
  list($users, $selected, $pagehead, $pagetitle) = filterUsers($_POST['user'], $pagetitle);
}
//dump('land');
//on landing try client; a single client will redirect to form.html.php, a multi team client will prepare variables for users.html.php

if ($users === []) {
  include CONNECT;
  $st = doQuery($pdo, "SELECT domain FROM client LEFT JOIN user ON $domainstr = client.domain WHERE user.id=$key", '');
  $row = $st->fetch(PDO::FETCH_ASSOC);
  $domain = $row['domain'] ?? NULL;
  if ($domain && !isset($prompt)) {
    list($users, $selected, $pagehead, $pagetitle) = filterUsers($row['domain'], $pagetitle, $error);
  }
}

//if $prompt is set and we have a one member client this will yield an empty set
if ($users === []) {
  include CONNECT;
  if (!$admin) {
    $sql .= " AND user.id=$key";
  }
  $sql .= " ORDER BY name";
  $st = doQuery($pdo, $sql, '');
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $row) {
    $users[$row['id']] = $row['name'];
  }
}

//prepare list
if ($admin) {
  include CONNECT;
  $result = doQuery($pdo, "SELECT client.domain, client.name FROM client ORDER BY name", 'Database error fetching clients:');
  $rows = $result->fetchAll();
  foreach ($rows as $row) {
    $client[$row['domain']] = $row['name'];
  }
}

//include CONNECT;
//reOrderTable($pdo);
//reAssignClient($pdo);


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
