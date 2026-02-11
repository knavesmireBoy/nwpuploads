<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/access.inc.php';
$super = "andrewsykes@btinternet.com";
$users = [];
$id = '';
$error = '';
$manage = "Edit details";
$message = '';
$denied = false;
$userdom = isset($_GET['userdom']) ? $_GET['userdom'] : NULL;
$clientdom = isset($_GET['clientdom']) ? $_GET['clientdom'] : NULL;
$pwd = isset($_GET['pwd']) ? $_GET['pwd'] : NULL;
$domainstr = "RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))";
$lib = ['nousers' => "<h4>Unable to find any users</h4>", "addnotice" => "Please fill required fields", "selectuser" => "Please select a user for editing", "clientdom" => "Cannot assign this user to a new client", "lastuser" => "To remove this last user, please delete the client instead",  "userdom" => "Changing your email address will require you to log out", "denied" => "You do not have the required privileges to delete, please contact your administrator", "access" => "You do not have the privileges to add a user", "deniedbyadmin" => "Cannot delete this user until a new client admin role is assigned to this client", "self" => "Only a peer can perform this deletion"];
$is_client_sql = "SELECT client.id AS employer, domain FROM client LEFT JOIN user ON $domainstr = client.domain WHERE user.email=:email";

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

//returns an id IF NOT already present and it becomes a signal to update the client id
//this could be overridden if a user desired a "contract" status
//ie they could up sticks by changing their email address with impunity
function isContractor($pdo, $sql, $email, $clientid = NULL)
{
  $st = $pdo->prepare($sql);
  $st->bindValue(':email', $email);
  doPreparedQuery($st, 'Error querying client credentials');
  $row = $st->fetch(PDO::FETCH_ASSOC);
  return (isset($row['employer']) && is_null($clientid)) ? $row['employer'] : $clientid;
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
}

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
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/login.html.php';
  exit();
}

if (!$roleplay = userHasWhatRole()) {
  $error = 'Only Account Administrators may access this page!';
  include 'accessdenied.html.php';
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
    $danger || count($roles) < 2;
    $denied = ($role === 'Client');
    if (!$denied && !$danger) {
      deleteAlready($pdo, $id);
    }
    else {
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
  $pagetitle = 'New User';
  //$action = '?';
  $button = 'Add User';
  $name = '';
  $email = '';
  $job = '';
  $id = '';
  $admin = ($priv === 'Admin');
  $clientadmin = preg_match("/admin/i", $priv) || preg_match("/client/i", $priv);

  if (!$clientadmin) {
    header("Location: ./?access");
    exit();
  }

  $st = doQuery($pdo, "SELECT id, description FROM role", "Error fetching list of roles.");
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  //Build the list of roles
  foreach ($rows as $row) {
    $roles[] = array('id' => $row['id'], 'description' => $row['description'], 'selected' => FALSE);
  }

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
  include 'form.html.php';
  exit();
} //////////////END OF ASSIGN


if (isset($_POST['action']) && $_POST['action'] === 'Add') {
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $clientid = $_POST['employer'] !== '' ? $_POST['employer'] : NULL;
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
  $st->bindValue(':clientid', $clientid);
  $res = doPreparedQuery($st, 'Error adding user');
  $aid = lastInsert($pdo);
  $contractor = isContractor($pdo, $is_client_sql,  $_POST['email'], $clientid);
  if (isset($_POST['password']) && $_POST['password'] != '') {
    $res = updatePassword($pdo, $_POST['password'], $aid);
  }
  $roles = isset($_POST['roles']) ? $_POST['roles'] : [];

  if ($clientid || $contractor) {
    $st = doQuery($pdo, "SELECT domain FROM client WHERE id='$clientid'", "Error retrieving clients from database!");
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $truedom = $row['domain'];
    $sql = "SELECT $domainstr AS dom FROM user WHERE client_id=:cid AND id=:aid";
    $st = $pdo->prepare($sql);
    $st->bindValue(':aid', $aid);
    $st->bindValue(':cid', $clientid);
    doPreparedQuery($st, 'Error fetching domain.');
    $row = $st->fetch(PDO::FETCH_ASSOC);
    updateUserDomain($row['dom'], $truedom);
    if ($contractor) {
      doQuery($pdo, "UPDATE user INNER JOIN client ON $domainstr = client.domain SET client_id='$contractor' WHERE $domainstr = client.domain", "Error updating client");
    }
  }
  resetRoles($pdo, $roles, $aid);
  header('Location: .');
  exit();
} //end of addform


if ((isset($_GET['edit'])) || $userdom || $pwd || $clientdom) {
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $clientAdmin = preg_match('/admin/i', $priv) && preg_match('/client/i', $priv);

  $id = isset($_GET['edit']) ? $_GET['edit'] : (isset($userdom) ? $userdom : ($pwd ? $pwd : NULL));
  $id = $id ? $id : $_POST['id'];
  $st = $pdo->prepare("SELECT id, name, email, $domainstr AS dom FROM user WHERE id =:id");
  $st->bindValue(":id", $id);
  doPreparedQuery($st, "<p>Error fetching user details.</p>");
  $row = $st->fetch(PDO::FETCH_ASSOC);
  $editor = $_SESSION['email'] === $row['email'];
  $warning = 'Polite Notice: changing an email or password will automatically log you out.';
  $message = ($userdom || $pwd || $editor) ? $warning : '';
  $message = $message ? $message : ($clientdom ? 'You do not have sufficient privileges to change the domain name. Please contact the database administrator.' : '');

  if ($message && ($message === $warning) && isset($_GET['userdom'])) {
    $message .= ' You can proceed but you will need to log in again with your updated details.';
  }
  if ($clientdom) {
    $st = $pdo->prepare("SELECT id, name, email, $domainstr AS dom FROM user WHERE email LIKE :dom");
    $st->bindValue(":dom", "%$clientdom");
    doPreparedQuery($st, "<p>Error fetching user details.</p>");
    $row = $st->fetch(PDO::FETCH_ASSOC);
  }
  $route = "Edit";
  $pagetitle = 'Edit User';
  $action = 'editform';
  $button = 'Update User';
  $name = $row['name'];
  $email = $row['email'];
  $id = $row['id'];

  $override = $userdom ? $userdom : ($pwd ? $pwd : NULL);

  $st = $pdo->prepare("SELECT roleid FROM userrole WHERE userid=:id");
  $st->bindValue(":id", $id);
  doPreparedQuery($st, "<p>Error fetching list of assigned roles.</p>");
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  $selectedRoles = array();
  foreach ($rows as $row) {
    $selectedRoles[] = $row['roleid'];
  }
  // Build the list of all roles
  $st = doQuery($pdo, "SELECT id, description FROM role", '<p>Error fetching list of roles.</p>');
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $row) {
    $_roles[] = array('id' => $row['id'], 'description' => $row['description'], 'selected' => in_array($row['id'], $selectedRoles));
  }
  $st = doQuery($pdo, "SELECT id, name FROM client ORDER BY name", '<p>Error retrieving clients from database!</p>');
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $row) {
    $clientlist[$row['id']] = $row['name'];
  }
  $roles = [];
  if ($clientAdmin) {
    foreach ($_roles as $role) {
      if ($role['id'] !== 'Admin') {
        $roles[] = $role;
      }
    }
    unset($clientlist);
  } else {
    $roles = $_roles;
  }

  $st = $pdo->prepare("SELECT client_id FROM user WHERE id=:id");
  $st->bindValue(":id", $id);
  doPreparedQuery($st, "<p>Error retrieving client id from user!</p>");
  $row = $st->fetch(PDO::FETCH_ASSOC);
  $job = $row['client_id']; //selects client in drop down menu
  include 'form.html.php';
  exit();
} //edit

if (isset($_POST['action']) && $_POST['action'] === 'Edit') {
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $override = $_POST['override'];
  $id =  $_POST['id'];

  if (isset($_POST['delete'])) {
    $id = $_POST['id'];
    $title = "Prompt";
    $prompt = "Are you sure you want to delete this user? ";
    $call = "confirm";
    $pos = "Yes";
    $neg = "No";
    $action = '';
  } else {
    $roles = isset($_POST['roles']) ? $_POST['roles'] : [];
    $clientid = empty($_POST['employer']) ? NULL : intval($_POST['employer']);
    //work out if freelancer...
    $sql = "SELECT id FROM user WHERE client_id IS NULL AND id=:id";
    $st = $pdo->prepare($sql);
    $st->bindValue(':id',  $_POST['id']);
    doPreparedQuery($st, 'Error fetching client.');
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $freelance = $row['id'];

    $sql = "SELECT domain AS dom FROM client WHERE id=:cid";
    $st = $pdo->prepare($sql);
    $st->bindValue(':cid',  $clientid);
    doPreparedQuery($st, 'Error fetching client.');
    $row = $st->fetch(PDO::FETCH_ASSOC);

    $i = strpos($_POST['email'], '@');
    $edom = substr($_POST['email'], $i + 1);

    $sql = "SELECT email, $domainstr AS dom FROM user WHERE client_id=:cid AND id=:id";
    $st = $pdo->prepare($sql);
    $st->bindValue(':id', $id);
    $st->bindValue(':cid', $clientid);
    doPreparedQuery($st, 'Error fetching user.');
    $oldrow = $st->fetch(PDO::FETCH_ASSOC);
    $dom = $oldrow['dom'] ?? NULL;
    $email = $oldrow['email'] ?? NULL;

    if (!$freelance && ($edom !== $dom)) {
      header("Location: ./?clientdom=$dom");
      exit();
    }

    if (($freelance && !$override) && ($edom !== $dom)) {
      header("Location: ./?userdom=$freelance");
      exit();
    }

    $sql = "UPDATE user SET name=:name, email=:email WHERE id=:id";
    $st = $pdo->prepare($sql);
    $st->bindValue(":name", $_POST['name']);
    $st->bindValue(":email", $_POST['email']);
    $st->bindValue(":id", $id);
    doPreparedQuery($st, '<p>Error setting user details.</p>');

    $echange  = $_POST['email'] !== $email;
    $editor = $_SESSION['email'] === $email;

    if ($override || ($echange && $editor)) {
      header("Location: ../?action=logout");
      exit();
    }

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
      $st->bindValue(":id", $_POST['id']);
      doPreparedQuery($st, '<p>Error removing obsolete user role entries.</p>');
    }
    resetRoles($pdo, $roles, $_POST['id']);
    //$clientid is allowed to be null if a user wants to disassociate from a client
    $sql = "UPDATE user SET client_id=:cid WHERE id =:id";
    $st = $pdo->prepare($sql);
    $st->bindValue(":cid", $clientid);
    $st->bindValue(":id", $_POST['id']);
    doPreparedQuery($st, '<p>Error setting client id</p>');
    updateUserDomain($edom, $dom, $_POST['id']);
    header('Location: .');
    exit();
  } //not delete
} ///END OF editform

//display users___________________________________________________________________
$sql = "SELECT user.id, user.name FROM user LEFT JOIN (SELECT user.name, client.domain FROM user INNER JOIN client ON $domainstr=client.domain) AS employer ON $domainstr=employer.domain WHERE employer.domain IS NULL"; //this overwrites above query to filter out users as employees
$sql = "SELECT user.id, user.name FROM user LEFT JOIN client ON user.client_id=client.id WHERE client.domain IS NULL"; //USING ID NOT DOMAIN
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
//_______________________________________________________________________________

if (isset($_POST['act']) && $_POST['act'] == 'Choose') {
  if ($_POST['user'] === '') {
    header("Location: ./?selectuser");
    exit();
  }
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
    include 'users.html.php';
  } else {
    $sql .= " AND user.id=$key";
  }
} ///CHOOSE________________________________________________________________________

if (!preg_match("/admin/i", $priv)) {
  $sql .= " AND user.id=$key";
  $manage = "Edit details";
}

$sql .= " ORDER BY name";

if (!isset($flag)) {
  $result = doQuery($pdo, $sql, 'Error retrieving list:');
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
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $st = $pdo->prepare("SELECT $domainstr FROM user WHERE user.email=:email");
  $st->bindValue(":email", $email);
  $res = doPreparedQuery($st, 'Error retrieving list:');
  $row = $res ? $st->fetch(PDO::FETCH_NUM) : null;
  $dom = isset($row) ? $row[0] : null;
  $flag = true;

  if ($dom) {
    //https://stackoverflow.com/questions/18511645/use-bound-parameter-multiple-times
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
      if (!$res) {
        $error = 'Database error fetching client list.' . $sql;
        include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
        exit();
      }
      $rows = $st->fetchAll(PDO::FETCH_ASSOC);
      foreach ($rows as $row) {
        $users[$row['id']] = $row['name'];
      }
    }
    $denied = clientCheck($flag);
    include 'users.html.php';
    exit();
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

$error =  $lib[$_SERVER["QUERY_STRING"]] ?? '';
$message = $message ? $message : $error;
include 'users.html.php';
