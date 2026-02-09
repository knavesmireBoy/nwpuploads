<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/access.inc.php';
$users = [];
$id = '';
$error = '';
$manage = "Edit details";
$message = '';
$userdom = isset($_GET['userdom']) ? $_GET['userdom'] : NULL;
$clientdom = isset($_GET['clientdom']) ? $_GET['clientdom'] : NULL;
$pwd = isset($_GET['pwd']) ? $_GET['pwd'] : NULL;
$domainstr = "RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))";
$lib = ['nousers' => "<h4>Unable to find any users</h4>", "addnotice" => "Please fill required fields", "selectuser" => "Please select a user for editing", "clientdom" => "Cannot assign this user to a new client", "lastuser" => "To remove this last user, please delete the client instead",  "userdom" => "Changing your email address will require you to log out", "denied" => "You do not have the required privileges to delete, please contact your administrator"];

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


//admin page

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

if (isset($_POST['action']) and $_POST['action'] == 'Delete') {
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
    $id = $_POST['id'];
    $sql = "SELECT domain FROM user INNER JOIN client ON user.client_id = client.id WHERE user.id=:id";

    $st = $pdo->prepare($sql);
    $st->bindValue(':id',  $id);
    doPreparedQuery($st, 'Error fetching client.');
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $dom = $row['domain'];
    $sql = "SELECT user.id FROM user INNER JOIN client ON user.client_id = client.id WHERE client.domain='$dom'";
    $st = doQuery($pdo, $sql, 'Error fetching client.');
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $denied = !clientCheck();
    if (count($rows) === 1) {
      header("Location: ./?lastuser");
      exit();
    }
    if (!$denied) {
      deleteAlready($pdo, $_POST['id']);
    }
    else {
      $location .= "/?denied";
    }
  }
  header("Location: $location");
  exit();
} ////////////END OF DELETE

if (isset($_GET['denied'])) {
  $error =  $lib[$_SERVER["QUERY_STRING"]] ?? '';
}
if (isset($_GET['add'])) {
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $pagetitle = 'New User';
  $action = 'addform';
  $name = '';
  $email = '';
  $button = 'Add User';

  //Build the list of roles
  $sql = "SELECT id, description FROM role";
  $st = doQuery($pdo, $sql, "<p>Error retrieving details</p>");
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  if (!$rows) {
    $error = 'Error fetching list of roles.';
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
    exit();
  }
  foreach ($rows as $row) {
    $roles[] = array('id' => $row['id'], 'description' => $row['description'], 'selected' => FALSE);
  }
  //cannot see how we have $_POST here
  if (isset($_POST['employer']) && !empty($_POST['employer'])) {
    $st = doQuery($pdo, "SELECT id, domain FROM client WHERE id=$id", "Error retrieving clients from database!");
    if (!$st) {
      $error = "Error retrieving clients from database!";
      include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
      exit();
    }
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $cid = $row['id'];
    $email = $row['domain'];

    $sql = "SELECT id, name FROM client ORDER BY name";
    $st = doQuery($pdo, $sql, "<p>Error retrieving client from database!</p>");
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
      $error = "Error retrieving client from database!";
      include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
      exit();
    }
  }
  include 'form.html.php';
  exit();
} //////////////END OF ASSIGN


if (isset($_GET['addform'])) {
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $sql = "INSERT INTO user (name, email, password, client_id) VALUES(:nom, :email,:pwd, :clientid)";
  $clientId = empty($_POST['employer']) ? NULL : intval($_POST['employer']);
  $st = $pdo->prepare($sql);
  $essentials = [$_POST['name'], $_POST['email'], $_POST['password']];
  $essentials = array_filter($essentials, function ($item) {
    return $item;
  });

  $roles = isset($_POST['roles']) ? $_POST['roles'] : [];

  if (count($essentials) < 3) {
    header("Location: ./?addnotice");
    exit();
  }

  $st->bindValue(':nom', $_POST['name']);
  $st->bindValue(':email', $_POST['email']);
  $st->bindValue(':pwd', $_POST['password']);
  $st->bindValue(':clientid', $clientId);
  $res = doPreparedQuery($st, 'Error adding user!!');

  if (!$res) {
    $error = 'Error adding user.';
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
    exit();
  }
  $aid = lastInsert($pdo);
  if (isset($_POST['password']) && $_POST['password'] != '') {
    $res = updatePassword($pdo, $_POST['password'], $aid);
  }

  if ($clientId) {
    $sql = "UPDATE user SET client_id=:cid WHERE id=:aid";
    $st = $pdo->prepare($sql);
    $st->bindValue(':cid',  $clientId);
    $st->bindValue(':aid', $aid);
    $res = doPreparedQuery($st, 'Error setting client id.');

    if (!$res) {
      $error = 'Error setting client id.';
      include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
      exit();
    }
    $sql = "SELECT domain AS dom FROM client WHERE id=:cid";
    $st = $pdo->prepare($sql);
    $st->bindValue(':cid',  $clientId);
    doPreparedQuery($st, 'Error fetching client.');
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $sql = "SELECT $domainstr AS dom FROM user WHERE client_id=:cid AND id=:aid";
    $st = $pdo->prepare($sql);
    $st->bindValue(':aid', $aid);
    $st->bindValue(':cid', $clientId);
    doPreparedQuery($st, 'Error fetching user.');
    $oldrow = $st->fetch(PDO::FETCH_ASSOC);
    updateUserDomain($oldrow['dom'], $row['dom']);
  }
  resetRoles($pdo, $roles, $aid);
  header('Location: .');
  exit();
} //end of addform


if ((isset($_POST['action']) && $_POST['action'] == 'Edit') || $userdom || $pwd || $clientdom) {
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $clientAdmin = preg_match('/admin/i', $priv) && preg_match('/client/i', $priv);
  $id = isset($_POST['id']) ? $_POST['id'] : (isset($userdom) ? $userdom : ($pwd ? $pwd : NULL));

  $st = $pdo->prepare("SELECT id, name, email, $domainstr AS dom FROM user WHERE id =:id");
  $st->bindValue(":id", $id);
  doPreparedQuery($st, "<p>Error fetching user details.</p>");
  $row = $st->fetch(PDO::FETCH_ASSOC);
  $editor = $_SESSION['email'] === $row['email'];

  $warning = 'Polite Notice: changing an email or password will automatically log you out. Please log in again with your updated details.';

  $message = ($userdom || $pwd || $editor) ? $warning : '';
  $message = $message ? $message : ($clientdom ? 'You do not have sufficient privileges to change the domain name. Please contact the database administrator.' : '');

  if ($clientdom) {
    $st = $pdo->prepare("SELECT id, name, email, $domainstr AS dom FROM user WHERE email LIKE :dom");
    $st->bindValue(":dom", "%$clientdom");
    doPreparedQuery($st, "<p>Error fetching user details.</p>");
    $row = $st->fetch(PDO::FETCH_ASSOC);
  }

  $pagetitle = 'Edit User';
  $action = 'editform';
  $name = $row['name'];
  $email = $row['email'];
  $id = $row['id'];
  $button = 'Update User';
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

if (isset($_GET['editform'])) {
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $override = $_POST['override'];
  $roles = isset($_POST['roles']) ? $_POST['roles'] : [];
  $id =  $_POST['id'];
  $clientId = empty($_POST['employer']) ? NULL : intval($_POST['employer']);
  //work out if freelancer...
  $sql = "SELECT id FROM user WHERE client_id IS NULL AND id=:id";
  $st = $pdo->prepare($sql);
  $st->bindValue(':id',  $_POST['id']);
  doPreparedQuery($st, 'Error fetching client.');
  $row = $st->fetch(PDO::FETCH_ASSOC);
  $freelance = $row['id'];

  $sql = "SELECT domain AS dom FROM client WHERE id=:cid";
  $st = $pdo->prepare($sql);
  $st->bindValue(':cid',  $clientId);
  doPreparedQuery($st, 'Error fetching client.');
  $row = $st->fetch(PDO::FETCH_ASSOC);

  $i = strpos($_POST['email'], '@');
  $edom = substr($_POST['email'], $i + 1);

  $sql = "SELECT email, $domainstr AS dom FROM user WHERE client_id=:cid AND id=:id";
  $st = $pdo->prepare($sql);
  $st->bindValue(':id', $id);
  $st->bindValue(':cid', $clientId);
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

  $echange  = $_POST['email'] != $email;
  $editor = $_SESSION['email'] === $email;

  if ($override || ($echange && $editor)) {
    header("Location: ./?action=logout");
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
  //$clientId is allowed to be null if a user wants to disassociate from a client
  $sql = "UPDATE user SET client_id=:cid WHERE id =:id";
  $st = $pdo->prepare($sql);
  $st->bindValue(":cid", $clientId);
  $st->bindValue(":id", $_POST['id']);
  doPreparedQuery($st, '<p>Error setting client id</p>');

  updateUserDomain($edom, $dom, $_POST['id']);
  header('Location: . ');
  exit();
} ///END OF EDIT

//display users___________________________________________________________________
$sql = "SELECT user.id, user.name FROM user LEFT JOIN (SELECT user.name, client.domain FROM user INNER JOIN client ON $domainstr=client.domain) AS employer ON $domainstr=employer.domain WHERE employer.domain IS NULL"; //this overwrites above query to filter out users as employees
$sql = "SELECT user.id, user.name FROM user LEFT JOIN client ON user.client_id=client.id WHERE client.domain IS NULL"; //USING ID NOT DOMAIN
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
//_______________________________________________________________________________

if (isset($_POST['act']) and $_POST['act'] == 'Choose') {
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
  // some clients need full domain for identification, in which case the query is simplified to a straight match to a users email address which corresponds to the client domain.
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


if ($priv && $priv != "Admin") {
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
      $domainstr = "user.email";
    } //full domain

    if ($count > 0) {
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

    $denied = !clientCheck();
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
