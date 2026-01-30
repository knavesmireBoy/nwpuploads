<?php
/*mysqli_real_escape_string\(([^,]+),([^)]+\);)
mysqli_real_escape_string($2, $1);*/
require_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/access.inc.php';
$users = [];
$id = '';
if (!userIsLoggedIn()) {
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/login.html.php';
  exit();
}
//admin page

if (!$roleplay = userHasWhatRole()) {
  $error = 'Only Account Administrators may access this page!!';
  include 'accessdenied.html.php';
  exit();
}
$sql = "SELECT id, name FROM user "; // THE DEFAULT QUERY___________________________________
foreach ($roleplay as $key => $priv):
  if ($priv == 'Client') {
    // constrains the query to one user if a client is logged in
    $sql = "SELECT id, name FROM user where id ='$key' ORDER BY name";
  }
endforeach;

if (isset($_POST['action']) and $_POST['action'] == 'Delete') {
  $id = $_POST['id'];
  $title = "Prompt";
  $prompt = "Are you sure you want to delete this user? ";
  $call = "confirm";
  $pos = "Yes";
  $neg = "No";
  $action = '';
  //include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/prompt.html.php';
  //exit(); 
}

if (isset($_POST['confirm']) and $_POST['confirm'] == 'Yes') {
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $id = mysqli_real_escape_string($link, $_POST['id']);
  $result = mysqli_query($link, "DELETE FROM user WHERE id = $id");
  if (!$result) {
    $error = 'Error deleting user.';
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
    exit();
  }
  header('Location: . ');
  exit();
}
if (isset($_POST['confirm']) and $_POST['confirm'] == 'No') {
  header('Location: . ');
  exit();
} ////////////END OF DELETE

/*OVERWRITING BELOW, WAS USED TO PROVIDE A CLIENT LIST DROP DOWN MENU
FOR PRE-SELECTING A DOMAIN PRIOR TO ADDING A NEW USER TO AN EXISITING CLIENT
NOT REALLY USED IN PRACTICE*/
if (isset($_GET['add'])) {
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $title = 'Prompt';
  $prompt = 'Employer:';
  $prompt = false;
  $action = 'assign';
  $sql = "SELECT id, name FROM client ORDER BY name";

  $st = doQuery($pdo, $sql, "<p>Error retrieving details</p>");
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  if (!$rows) {
    $error = "Error retrieving clients from database!";
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
    exit();
  }
  foreach ($rows as $row) {
    $clientlist[$row['id']] = $row['name'];
  }
  //include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/prompt.html.php';
  //exit();
} //////////////END OF ADD

//if (isset($_POST['action']) and $_POST['action'] == 'continue'){
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

  if (isset($_POST['employer']) && !empty($_POST['employer'])) {
    $id = mysqli_real_escape_string($link, $_POST['employer']);
    $sql = "SELECT id, domain FROM client WHERE id=$id";
    $result = mysqli_query($link, $sql);


    if (!$result) {
      $error = "Error retrieving clients from database!";
      include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
      exit();
    }

    $row = mysqli_fetch_array($result);
    $cid = $row['id'];
    $email = $row['domain'];

    $sql = "SELECT id, name FROM client ORDER BY name";
    $result = mysqli_query($link, $sql);
    if (!$result) {
      $error = "Error retrieving clients from database!";
      include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
      exit();
    }
    while ($row = mysqli_fetch_array($result)) {
      $clientlist[$row['id']] = $row['name'];
    }
  }
  include 'form.html.php';
  exit();
} //////////////END OF ASSIGN


if (isset($_GET['addform'])) {
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $name = mysqli_real_escape_string($link, $_POST['name']);
  $email = mysqli_real_escape_string($link, $_POST['email']);
  $sql = "INSERT INTO user SET name='$name', email='$email' ";
  if (!mysqli_query($link, $sql)) {
    $error = 'Error adding user.';
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
    exit();
  }
  $aid = mysqli_insert_id($link);
  if (isset($_POST['password']) && $_POST['password'] != '') {
    $password = md5($_POST['password'] . 'uploads');
    $password = mysqli_real_escape_string($link, $password);
    $sql = "UPDATE user SET password = '$password'  WHERE id = '$aid'";
    if (!mysqli_query($link, $sql)) {
      $error = 'Error setting user password.';
      include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
      exit();
    }
  }
  if (isset($_POST['employer']) && $_POST['employer'] != '') {
    $client = $_POST['employer'];
    $cid = mysqli_real_escape_string($link, $client);
    $sql = "UPDATE user SET client_id=$cid WHERE id=$aid";
    if (!mysqli_query($link, $sql)) {
      $error = 'Error setting client id 152.';
      include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
      exit();
    }
  }
  if (isset($_POST['roles'])) {
    foreach ($_POST['roles'] as $role) {
      $roleid = mysqli_real_escape_string($link, $role);
      $sql = "INSERT INTO userrole SET userid='$aid', roleid='$roleid'";
      if (!mysqli_query($link, $sql)) {
        $error = 'Error assigning selected role to user.';
        include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
        exit();
      }
    }
  }
  header('Location: . ');
  exit();
} //end of addform

if (isset($_POST['action']) and $_POST['action'] == 'Edit') {
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';

  $id = $_POST['id'];
  $st = $pdo->prepare("SELECT id, name, email FROM user WHERE id =:id");
  $st->bindValue(":id", $id);
  doPreparedQuery($st, "<p>Error fetching user details.</p>");

  $row = $st->fetch(PDO::FETCH_ASSOC);

  $pagetitle = 'Edit User';
  $action = 'editform';
  $name = $row['name'];
  $email = $row['email'];
  $id = $row['id'];
  $button = 'Update User';

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
    $roles[] = array('id' => $row['id'], 'description' => $row['description'], 'selected' => in_array($row['id'], $selectedRoles));
  }
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
  include 'form.html.php';
  exit();
} //edit


if (isset($_GET['editform'])) {


  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';

  $sql = "UPDATE user SET name=:name, email=:email WHERE id=:id";
  $st = $pdo->prepare($sql);
  $st->bindValue(":name", $_POST['name']);
  $st->bindValue(":email", $_POST['email']);
  $st->bindValue(":id", $_POST['id']);
  doPreparedQuery($st, '<p>Error setting user details.</p>');

  if (isset($_POST['password']) && $_POST['password'] != '') {
    $password = md5($_POST['password'] . 'uploads');
    $password = mysqli_real_escape_string($link, $password);
    $sql = "UPDATE user SET password =:password WHERE id =:id";
    $st = $pdo->prepare($sql);
    $st->bindValue(":password", $_POST['password']);
    $st->bindValue(":id", $_POST['id']);
    doPreparedQuery($st, '<p>Error setting user password.</p>');
  }

  if ($priv && $priv == 'Admin') {
    $sql = "DELETE FROM userrole WHERE userid=:id";
    $st = $pdo->prepare($sql);
    $st->bindValue(":id", $_POST['id']);
    doPreparedQuery($st, '<p>Error removing obsolete user role entries.</p>');
  }
  if (isset($_POST['roles'])) {
    foreach ($_POST['roles'] as $role) {
      $sql = "INSERT INTO userrole SET userid=:id, roleid=:rol";
      $st = $pdo->prepare($sql);
      $st->bindValue(":id", $_POST['id']);
      $st->bindValue(":rol", $role);
      doPreparedQuery($st, '<p>Error assigning selected role to user.</p>');
    } //end foreach
  }
  if (isset($_POST['employer']) && !empty($_POST['employer'])) {
    $sql = "UPDATE user SET client_id=:cid WHERE id =:id";
    $st = $pdo->prepare($sql);
    $st->bindValue(":cid", $_POST['employer']);
    $st->bindValue(":id", $_POST['id']);
    doPreparedQuery($st, '<p>Error setting client id </p>');
  }
  header('Location: . ');
  exit();
} ///END OF EDIT

//display users___________________________________________________________________
$domain = "RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))";
$sql = "SELECT user.id, user.name FROM user LEFT JOIN (SELECT user.name, client.domain FROM user INNER JOIN client ON $domain=client.domain) AS employer ON $domain=employer.domain WHERE employer.domain IS NULL"; //this overwrites above query to filter out users as employees

$sql = "SELECT user.id, user.name FROM user LEFT JOIN client ON user.client_id=client.id WHERE client.domain IS NULL"; //USING ID NOT DOMAIN

include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
//_______________________________________________________________________________

if (isset($_POST['act']) and $_POST['act'] == 'Choose' && isset($_POST['user']) && $_POST['user'] != '') {
  $return = "Return to users";
  $manage = "Manage Users";
  $key = $_POST['user'];

  // $key =  mysqli_real_escape_string($link, $_POST['user']);
  $sqlc = "SELECT domain FROM client WHERE domain=:domain";
  $st = $pdo->prepare($sqlc);
  $st->bindValue(":domain", $key);
  doPreparedQuery($st, "<p>Error:</p>");
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if (strrpos($key, "@")) { // some clients need full domain for identification, in which case the query is simplified to a straight match to a users email address which corresponds to the client domain.
    $domain = "user.email";
  }
  if ($row) {
    $sqlc = "SELECT employer.user_name, employer.user_id FROM (SELECT user.name AS user_name, user.id AS user_id, client.domain FROM user INNER JOIN client ON $domain=client.domain) AS employer WHERE employer.domain=:domain"; //
    $st = $pdo->prepare($sqlc);
    $st->bindValue(":domain", $row['domain']);

    doPreparedQuery($st, "<p>'Database error fetching users.'</p>");
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
      $error = 'Database error fetching users.' . $sql;
      include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
      exit();
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

if ($priv && $priv != "Admin") {
  $email = "{$_SESSION['email']}";
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $sqlc = "SELECT $domain FROM user WHERE user.email='$email'";
  $result = mysqli_query($link, $sqlc);
  $row = mysqli_fetch_array($result);
  $dom = $row[0];
  $sqlc = "SELECT COUNT(*) AS dom FROM user INNER JOIN client ON $domain=client.domain WHERE $domain='$dom' AND client.domain='$dom'";

  $result = doQuery($pdo, $sqlc, 'Error retrieving list:');
  $row = $result->fetch();
  $count = $row['dom'];
  if ($count == 0) $domain = "user.email"; //full domain
  if (count($count) > 0) {
    $sql = "SELECT employer.id, employer.name FROM user INNER JOIN (SELECT user.id, user.name, client.domain FROM user INNER JOIN client ON $domain=client.domain) AS employer ON $domain=employer.domain WHERE user.email='$email'";
    $result = mysqli_query($link, $sql);

    if (!$result) {
      $error = 'Database error fetching client list.' . $sql;
      include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
      exit();
    }
    while ($row = mysqli_fetch_array($result)) {
      $users[$row['id']] = $row['name'];
    }
    include 'users.html.php';
  }
}

if ($priv && $priv == "Admin") {
  $manage = "Manage Users";
  $sqlc = "SELECT client.domain, client.name FROM client ORDER BY name";
  $result = doQuery($pdo, $sqlc, 'Error retrieving list:');

  if (!$result) {
    $error = 'Database error fetching clients.';
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
    exit();
  }
  $rows = $result->fetchAll();
  foreach ($rows as $row) {
    $client[$row['domain']] = $row['name'];
  }
}
include 'users.html.php';
