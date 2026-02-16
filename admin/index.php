<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/access.inc.php';

function query()
{
  $lib = ['nousers' => "<h4>Unable to find any users</h4>", "addnotice" => "Please fill required fields", "selectuser" => "Please select a user for editing", "clientflag" => "Cannot assign this user to a new client", "lastuser" => "To remove this last user, please delete the client instead",  "denied" => "You do not have the required privileges to delete, please contact your administrator", "access" => "You do not have the privileges to add a user", "deniedbyadmin" => "Cannot delete this user until a new client admin role is assigned to this client", "self" => "Only a peer can perform this deletion", "freelancer" => "Cannot assign this domain"];
  $query = explode('=', $_SERVER["QUERY_STRING"]);
  $q = $query[1] ?? $query[0];
  return $lib[$q] ?? '';
}

$super = "andrewsykes@btinternet.com";
$users = [];
$id = '';
$error = query();
$manage = "Edit details";
$message = '';
$denied = false;
$usercount = 0;
setExtent(null);
$selected = null;

$formvars = ['pagetitle', 'message', 'name', 'email', 'job', 'roles', 'id', 'route', 'override', 'button', 'priv'];
$uservars = ['manage', 'priv', 'client', 'users'];
$domainstr = "RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))";
$is_client_sql = "SELECT client.id AS employer, domain, email FROM client LEFT JOIN user ON $domainstr = client.domain WHERE user.email=:email";

$isContractor = function ($pdo, $email, $clientid = NULL) use ($is_client_sql) {
  $st = $pdo->prepare($is_client_sql);
  $st->bindValue(':email', $email);
  doPreparedQuery($st, 'Error querying client credentials');
  $row = $st->fetch(PDO::FETCH_ASSOC);
  return (isset($row['employer']) && is_null($clientid)) ? $row['employer'] : $clientid;
};

$clientflag = $_GET['clientflag'] ?? NULL;
$pwd = $_GET['pwd'] ?? NULL;


function updateUserDomain($old, $new, $id = 0)
{
  if ($old && $new) {
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
    $concat = replaceStrPos($new);
    //update email of employees IF the domain of client changes
    $sql = "UPDATE user SET email = $concat WHERE email LIKE '%$old'";
    //but restrict to a specific employee (eg leaving)
    if ($id) {
      $sql .= "  AND id='$id'";
    }
    doQuery($pdo, $sql, '');
  }
}

function isFreelancer($pdo, $id)
{
  $sql = "SELECT id FROM user WHERE client_id IS NULL AND id=:id";
  $st = $pdo->prepare($sql);
  $st->bindValue(':id',  $id);
  doPreparedQuery($st, 'Error fetching client.');
  $row = $st->fetch(PDO::FETCH_ASSOC);
  return $row['id'];
}
//object and prop or; no prop object MUST be a domain
function isEmployer($o, $p = '')
{
  $domainstr = "RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))";
  $sql = "SELECT client.id AS employer, domain, email FROM client LEFT JOIN user ON $domainstr = client.domain";
  $id = null;
  if (!$p) {
    $sql = "SELECT client.id AS employer, domain, name FROM client WHERE client.domain = '$o'";
  }
  if ($p === 'email') {
    $sql .= "  WHERE user.email=:id";
    $id = $o[$p];
  }
  if ($p === 'id') {
    $sql .= "  WHERE user.id=:id";
    $id = $o[$p];
  }

  if ($p === 'employer') {
    $id = $o[$p];
    $sql = "SELECT client.id, domain FROM client WHERE client.id =:id";
  }

  return function ($pdo) use ($id, $sql) {
    $st = $pdo->prepare($sql);
    if (isset($id)) {
      $st->bindValue(':id',  $id);
    }
    doPreparedQuery($st, 'Error fetching client.');
    return $st->fetch(PDO::FETCH_NUM);
  };
}


function fetchAllRoles($pdo, $selectedRoles = [])
{
  //Build the list of all roles
  $st = doQuery($pdo, "SELECT id, description FROM role", '<p>Error fetching list of roles.</p>');
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $row) {
    $roles[] = array('id' => $row['id'], 'description' => $row['description'], 'selected' => in_array($row['id'], $selectedRoles));
  }
  return $roles;
}

function fetchSelectedRoles($pdo, $id)
{
  $st = $pdo->prepare("SELECT roleid FROM userrole WHERE userid=:id");
  $st->bindValue(":id", $id);
  doPreparedQuery($st, "<p>Error fetching list of assigned roles.</p>");
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $row) {
    $selectedRoles[] = $row['roleid'];
  }
  return fetchAllRoles($pdo, $selectedRoles);
}

function resetRoles($pdo, $roles, $id)
{
  foreach ($roles as $role) {
    $sql = "INSERT INTO userrole SET userid=:id, roleid=:rol";
    $st = $pdo->prepare($sql);
    $st->bindValue(":id", $id);
    $st->bindValue(":rol", $role);
    doPreparedQuery($st, '<p>Error assigning selected role to user.</p>');
  } //end foreach
  return $roles;
}
//maybe require password of client to delete??
function deleteAlready($pdo, $id)
{
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

if (isset($_GET['domain'])) {
  updateUserDomain($_GET['domain'], $_GET['updated']);
}
if (!userIsLoggedIn()) {
  $pagetitle = "Log In";
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/login.html.php';
  exit();
}

if (!$roleplay = userHasWhatRole(true)) {
  $error = 'Only Account Administrators may access this page!';
  $pagetitle = "Access Denied";
  include TEMPLATE . 'accessdenied.html.php';
  exit();
}
$sql = "SELECT id, name FROM user "; // THE DEFAULT QUERY___________________________________
list($key, $priv) = $roleplay;

if (preg_match("/client/i", $priv)) {
  // constrains the query to one user if a client is logged in
  $sql = "SELECT id, name FROM user where id ='$key' ORDER BY name";
}

if (isset($_POST['action']) && $_POST['action'] === 'Delete') {
  $id = $_POST['id'];
  $title = "Prompt";
  $prompt = "Are you sure you want to delete this user? ";
  $call = "confirm";
  $pos = "Yes";
  $neg = "No";
  $action = '';
}

if (isset($_POST['confirm'])) {
  $location = " .";
  if ($_POST['confirm'] == 'Yes') {
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
    $admin = ($priv == 'Admin');
    $id = $_POST['id'];
    $role = null;
    $roles = [];
    $sql = "SELECT email, domain FROM user INNER JOIN client ON user.client_id = client.id WHERE user.id=:id";
    $st = $pdo->prepare($sql);
    $st->bindValue(':id',  $id);
    doPreparedQuery($st, 'Error fetching client.');
    $row = $st->fetch(PDO::FETCH_ASSOC);


    //https: //stackoverflow.com/questions/20009076/php-undefined-index-notice-not-raised-when-indexing-null-variable
    $dom = $row['domain'];
    $email = $row['email'];

    if (!$dom) {
      if (!$role) { //must be a freelancer/admin
        $st = doQuery($pdo, "SELECT email from user where id=$id", "fail");
        $email = $st->fetch(PDO::FETCH_ASSOC)['email'];
        if ($admin && ($email === $_SESSION['email'])) {
          header("Location: ./?self");
          exit();
        } else {
          deleteAlready($pdo, $id);
          header("Location: .");
          exit();
        }
      }
    }

    $sql = "SELECT user.id FROM user INNER JOIN client ON user.client_id = client.id WHERE client.domain='$dom'";
    $st = doQuery($pdo, $sql, 'Error fetching client.');
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $rolesql = "SELECT role.id AS role, userrole.userid AS id FROM role LEFT JOIN userrole ON role.id = userrole.roleid WHERE userrole.userid=:id";
    $st = $pdo->prepare($rolesql);
    if (!empty($rows)) {
      foreach ($rows as $r) {
        $st->bindValue(':id',  $r['id']);
        doPreparedQuery($st, 'Error fetching client.');
        $ro = $st->fetch(PDO::FETCH_ASSOC);
        $roles[$ro['id']] = $ro['role'];
      }
    }
    if (count($rows) === 1) {
      header("Location: ./?lastuser");
      exit();
    }
    $role = isset($roles[$id]) ? $roles[$id] : NULL;

    $danger = preg_match("/admin/i", $role);
    if ($danger) {
      $roles = safeFilter($roles, function ($role) {
        return preg_match("/admin/i", $role);
      });
    }
    $danger = $danger || count($roles) < 2;

    $denied = $admin ? false : ($role === 'Client');
    if (!$denied && !$danger) {
      deleteAlready($pdo, $id);
    } else {
      $location .= "/?denied";
    }
  }
  header("Location: $location");
  exit();
} ////////////END OF CONFIRM

if (isset($_GET['denied']) || isset($_GET['access']) || isset($_GET['self'])) {
  $error =  $lib[$_SERVER["QUERY_STRING"]] ?? '';
}

if (isset($_GET['add'])) {
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $route = "Add";
  $pagehead = 'New User';
  //$action = '?';
  $button = 'Add User';
  $name = '';
  $email = '';
  $job = '';
  $id = '';
  $override = '';
  $admin = ($priv === 'Admin');
  $clientadmin = preg_match("/admin/i", $priv) || preg_match("/client/i", $priv);

  if (!$clientadmin) {
    header("Location: ./?access");
    exit();
  }

  $roles = fetchAllRoles($pdo);

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
    $job = empty($row) ? NULL : $row['employer'];
    $roles = safeFilter($roles, function ($role) {
      return $role['id'] !== 'Admin';
    });
  }
  $pagetitle = "Admin | Users";
  $pagehead = "Add User";
  include 'form.html.php';
  exit();
} //////////////END OF ASSIGN


if (isset($_POST['action']) && $_POST['action'] === 'Add') {
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $clientid = $_POST['employer'] ?? NULL;
  $clientadmin = preg_match("/admin/i", $priv) && preg_match("/client/i", $priv);
  $essentials = [$_POST['name'], $_POST['email'], $_POST['password']];
  $essentials = array_filter($essentials, function ($item) {
    return $item;
  });

  if (count($essentials) < 3) {
    header("Location: ./?addnotice");
    exit();
  }
  $sql = "INSERT INTO user (name, email, password, client_id) VALUES(:nom, :e,:pwd, :clientid)";
  $st = $pdo->prepare($sql);
  /*
  applies to admin users only as $_POST['employer'] is set by default by client
  if the email field corresponds to an existing client AND there is no selection in the assign to client drop down $_POST['employer'] then we can correct with the isContractor function
  if we have a clientid provided by $_POST['employer'] we must check that the supplied email DOMAIN matches
  Potentially there may be a case for having a contractor role but it would have to be deleted if the client was removed from the database and without a clientid this would not happen through the referential integrity enforced by the database. But an additional check could be made at this point, in the meantime the "contractor" would be free to leave the role
  */
  $st->bindValue(':nom', $_POST['name']);
  $st->bindValue(':e', $_POST['email']);
  $st->bindValue(':pwd', $_POST['password']);
  $st->bindValue(':clientid', intval($clientid));
  $res = doPreparedQuery($st, 'Error adding user');
  $aid = lastInsert($pdo);
  $contractor = $isContractor($pdo,  $_POST['email'], $clientid);
  if (isset($_POST['password']) && $_POST['password'] != '') {
    $res = updatePassword($pdo, $_POST['password'], $aid);
  }
  $roles = isset($_POST['roles']) ? $_POST['roles'] : [];
  if ($clientid || $contractor) {
    $st = doQuery($pdo, "SELECT domain FROM client WHERE id=$clientid", "Error retrieving clients from database!");
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $truedom = $row['domain'];
    $sql = "SELECT $domainstr AS dom FROM user WHERE client_id=:cid AND id=:aid";
    $st = $pdo->prepare($sql);
    $st->bindValue(':aid', $aid);
    $st->bindValue(':cid', intval($clientid));
    doPreparedQuery($st, 'Error fetching domain.');
    $row = $st->fetch(PDO::FETCH_ASSOC);
    updateUserDomain($row['dom'], $truedom);

    if ($contractor) { //required?
      doQuery($pdo, "UPDATE user INNER JOIN client ON $domainstr = client.domain SET client_id=$contractor WHERE $domainstr = client.domain", "Error updating client");
    }
  }
  resetRoles($pdo, $roles, $aid);
  header('Location: .');
  exit();
} //end of addform


if ((isset($_GET['edit'])) ||  $pwd || $clientflag) {

  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $id = isset($_GET['edit']) ? $_GET['edit'] : ($pwd ? $pwd : NULL);
  $id = !empty($id) ? $id : $_POST['id'] ?? '';
  $st = $pdo->prepare("SELECT id, name, email, $domainstr AS dom FROM user WHERE id =:id");
  $st->bindValue(":id", $id);
  doPreparedQuery($st, "<p>Error fetching user details.</p>");
  $row = $st->fetch(PDO::FETCH_ASSOC);
  $editor = $_SESSION['email'] === $row['email'];
  $warning = 'Polite Notice: changing an email or password will automatically log you out.';
  $message = ($pwd || $editor) ? $warning : '';

  $message = $message ? $message : ($clientflag ? 'You do not have sufficient privileges to change the domain name. Please contact the database administrator.' : '');

  if ($message && ($message === $warning)) {
    $message .= ' You can proceed but you will need to log in again with your updated details.';
  }
  if ($clientflag) {
    $sql = "SELECT user.id, user.name, user.email FROM client LEFT JOIN user ON $domainstr = client.domain WHERE user.id=:dom";
    $st = $pdo->prepare($sql);
    $st->bindValue(":dom", $clientflag);
    doPreparedQuery($st, "<p>Error fetching user details.</p>");
    $row = $st->fetch(PDO::FETCH_ASSOC);
  }

  $pagetitle = "Edit Users";
  $route = "Edit";
  $pagehead = 'Edit User';
  $action = 'editform';
  $button = 'Update User';
  $roles = [];
  $clientlist = [];

  $id = $row['id'];
  $name = $row['name'];
  $email = $row['email'];
  $override = $pwd ? $pwd : NULL;

  $admin = ($priv === 'Admin');
  $clientadmin = preg_match("/admin/i", $priv) || preg_match("/client/i", $priv);
  $adminClient = preg_match('/admin/i', $priv) && preg_match('/client/i', $priv);

  if ($adminClient) {
    $st = $pdo->prepare("SELECT roleid FROM userrole WHERE userid=:id");
    $st->bindValue(":id", $id);
    doPreparedQuery($st, "<p>Error fetching list of assigned roles.</p>");
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
      $selectedRoles[] = $row['roleid'];
    }
    $roles = fetchAllRoles($pdo, $selectedRoles);
  }
  if ($admin) {
    $st = doQuery($pdo, "SELECT id, name FROM client ORDER BY name", '<p>Error retrieving clients from database!</p>');
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
      $clientlist[$row['id']] = $row['name'];
    }
    $st = $pdo->prepare("SELECT client_id FROM user WHERE id=:id");
    $st->bindValue(":id", $id);
    doPreparedQuery($st, "<p>Error retrieving client id from user!</p>");
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $job = $row['client_id']; //selects client in drop down menu
  }

  if ($adminClient) {
    $tmp = [];
    foreach ($roles as $role) {
      if ($role['id'] !== 'Admin') {
        $tmp[] = $role;
      }
    }
    $roles = $tmp;
  }

  include 'form.html.php';
  exit();
} //edit

if (isset($_POST['action']) && $_POST['action'] === 'Edit') {

  if (isset($_POST['delete'])) {
    $id = $_POST['id'];
    $title = "Prompt";
    $prompt = "Are you sure you want to delete this user? ";
    $call = "confirm";
    $pos = "Yes";
    $neg = "No";
    $action = '';
  } else {
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
    $override = $_POST['override'];
    $id = $_POST['id'];
    $roles = $_POST['roles'] ?? [];
    $assoc = false;
    $revert = false;
    $email = null;
    //$domcheck = true;
    $admin = $priv === 'Admin';
    $i = strpos($_POST['email'], '@');
    $edom = substr($_POST['email'], $i + 1);
    $isEmployer = isEmployer($edom);
    list($clientid, $domain) = $isEmployer($pdo);
    $freelancer = isFreelancer($pdo, $id);
    //$employerid only available from ADMIN; default to zero NOT NULL so it survives equality test with $clientid see $notice
    $employerid = empty($_POST['employer']) ? 0 : intval($_POST['employer']);

    $relocation = "Location: ./?clientflag=$id";
    //should user BE a freelancer
    if ($freelancer) {
      if (isset($clientid)) { //attempt by freelancer to join client; no priv
        $assoc = $admin ? true : false;
        header($relocation);
        exit();
      } else {
        $assoc = true;
      }
      $isEmployer = isEmployer($_POST, 'employer');
      list($clientid, $domain) = $isEmployer($pdo);
    } else {
      if (!$clientid) {
        //allow admin to = reinstate freelancer status
        if ($admin && !$employerid) {
          $domain = $edom;
        } else {
          $isEmployer = isEmployer($_POST, 'id');
          list($clientid, $domain, $email) = $isEmployer($pdo);
          $relocation = "Location: ./?clientflag=$id";
          header($relocation);
          exit();
        }
      }
    }
    $sql = "UPDATE user SET name=:name, email=:email";
    $sql .= $assoc ? ", client_id=:cid" : ' ';
    $sql .= " WHERE id=:id";
    $st = $pdo->prepare($sql);

    if ($assoc) {
      $st->bindValue(":cid", $clientid);
    }
    $st->bindValue(":name", $_POST['name']);
    $st->bindValue(":email", $revert ? $email : $_POST['email']);
    $st->bindValue(":id", $id);
    doPreparedQuery($st, '<p>Error setting user details.</p>');
    $editor = $_SESSION['email'] === $email;
    if (isset($_POST['password']) && $_POST['password'] != '') {
      if ($override) {
        $res = updatePassword($pdo, $_POST['password'], $id);
      } else {
        header("Location: ./?pwd=$id");
        exit();
      }
    }

    if (preg_match("/admin/i", $priv)) {
      $sql = "DELETE FROM userrole WHERE userid=:id";
      $st = $pdo->prepare($sql);
      $st->bindValue(":id", $id);
      doPreparedQuery($st, '<p>Error removing obsolete user role entries.</p>');
    }
    resetRoles($pdo, $roles, $id);
    //$clientid is allowed to be null if a user wants to disassociate from a client
    $sql = "UPDATE user SET client_id=:cid WHERE id =:id";
    $st = $pdo->prepare($sql);
    $st->bindValue(":cid", $clientid);
    $st->bindValue(":id", $id);
    doPreparedQuery($st, '<p>Error setting client id</p>');
    updateUserDomain($edom, $domain, $id);
    header('Location: .');
    exit();
  }  //not delete
}
///END OF editform

//display users___________________________________________________________________
$sql = "SELECT user.id, user.name FROM user LEFT JOIN (SELECT user.name, client.domain FROM user INNER JOIN client ON $domainstr=client.domain) AS employer ON $domainstr=employer.domain WHERE employer.domain IS NULL"; //this overwrites above query to filter out users as employees
$sql = "SELECT user.id, user.name FROM user LEFT JOIN client ON user.client_id=client.id WHERE client.domain IS NULL"; //USING ID NOT DOMAIN
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
//_______________________________________________________________________________

if (isset($_POST['action']) && $_POST['action'] == 'Choose') {
  if ($_POST['user'] === '') {
    header("Location: ./?selectuser");
    exit();
  }
  $selected = true;
  $return = "Return to users";
  $manage = "Manage Users";
  $key = $_POST['user'];
  $sqlc = "SELECT domain FROM client WHERE domain=:domain";
  $st = $pdo->prepare($sqlc);
  $st->bindValue(":domain", $key);
  doPreparedQuery($st, "<p>Error:</p>");
  $row = $st->fetch(PDO::FETCH_ASSOC);
  //some clients need full domain for identification, in which case the query is simplified to a straight match to a users email address which corresponds to the client domain.
  if (strrpos($key, "@")) {
    $domainstr = "user.email";
  }
  if ($row) {
    $sqlc = "SELECT employer.user_name, employer.user_id FROM (SELECT user.name AS user_name, user.id AS user_id, client.domain FROM user INNER JOIN client ON $domainstr=client.domain) AS employer WHERE employer.domain=:domain"; //
    $st = $pdo->prepare($sqlc);
    $st->bindValue(":domain", $row['domain']);
    doPreparedQuery($st, "<p>'Database error fetching users.'</p>");
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
      header("Location: ./?nousers");
    }
    foreach ($rows as $row) {
      $users[$row['user_id']] = $row['user_name'];
    }
    $flag = true;
    $class = "edit";

    $usercount = count($users);
    setExtent($usercount);

    if ($usercount === 1) {
      $key = $row['user_id'];
      $usercount = 1;
      header("Location: ./?edit=$key");
      exit;
    } else {
      $pagetitle = "Manage Users";
      include 'users.html.php';
    }
  } else {
    $sql .= " AND user.id=$key";
  }
} ///CHOOSE________________________________________________________________________


if (!preg_match("/admin/i", $priv)) {
  $sql .= " AND user.id=$key";
  $manage = "Edit details";
  $usercount = 1;
} else {
}
$sql .= " ORDER BY name";

if (!isset($flag)) {
  $result = doQuery($pdo, $sql, 'Error retrieving listo');
  $rows = $result->fetchAll();
  if (!$result) {
    $error = "Error retrieving users from t'database!";
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
    exit();
  }
  foreach ($rows as $row) {
    $users[$row['id']] = $row['name'];
  }
}

if (preg_match("/client/i", $priv)) {
  $email = $_SESSION['email'];
  $i = strpos($email, '@');
  $dom = substr($email, $i + 1);
  $flag = true;
  if ($dom) {
    //$users = []; //!!! reset
    //https://stackoverflow.com/questions/18511645/use-bound-parameter-multiple-times
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
    $sqlc = "SELECT COUNT(*) AS dom FROM user INNER JOIN client ON $domainstr=client.domain WHERE $domainstr=:dom AND client.domain=:dommo";
    $st = $pdo->prepare($sqlc);
    $st->bindValue(":dom", $dom);
    $st->bindValue(":dommo", $dom);
    doPreparedQuery($st, 'Error retrieving list:');
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $count = $row['dom'];
    if ($count == 0) {
      $flag = false;
      $domainstr = "user.email";
    } //full domain
    setExtent($count);
    if ($count > 0) {
      $users = []; //!!! reset
      $sql = "SELECT employer.id, employer.name FROM user INNER JOIN (SELECT user.id, user.name, client.domain FROM user INNER JOIN client ON $domainstr=client.domain";
      $sqlend = " AS employer ON $domainstr=employer.domain WHERE user.email=:email";
      if (!preg_match("/admin/i", $priv)) {
        $sqlkey = " WHERE user.id=:k)";
        $sql .= $sqlkey;
      } else {
        $sql .= ")";
      }
      $sql .= $sqlend;
      $st = $pdo->prepare($sql);
      $st->bindValue(":email", $email);
      if (isset($sqlkey)) {
        $st->bindValue(":k", $key);
      }
      doPreparedQuery($st, 'Error retrieving list:');
      $rows = $st->fetchAll(PDO::FETCH_ASSOC);

      foreach ($rows as $row) {
        $users[$row['id']] = $row['name'];
      }
    }
    $denied = clientCheck($flag);
    $usercount = $priv === 'Admin' ? 2 : count($users);
    setExtent($usercount);
    if ($usercount === 1) {
      //$key from $roleplay
      $k = $row['id'] ?? $key;
      $usercount = 1;
      header("Location: ./?edit=$k");
      exit;
    } else {
      $pagetitle = "Manage Users";
      include 'users.html.php';
      exit;
    }
  }
}

if (preg_match("/admin/i", $priv)) {
  $manage = "Manage Users";
  $sqlc = "SELECT client.domain, client.name FROM client ORDER BY name";
  $result = doQuery($pdo, $sqlc, 'Database error fetching clients:');

  $rows = $result->fetchAll();
  foreach ($rows as $row) {
    $client[$row['domain']] = $row['name'];
  }
}

include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';

//reOrderTable($pdo);
reAssignClient($pdo);

$message = $message ? $message : $error;
$usercount = $priv === 'Admin' ? 2 : count($users);

setExtent($usercount);
if ($usercount === 1) {
  $usercount = 1;
  header("Location: ./?edit=$key");
  exit;
} else {
  $pagetitle = "Manage Users";
  include 'users.html.php';
}
