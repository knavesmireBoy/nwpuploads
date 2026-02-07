<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/access.inc.php';
if (!userIsLoggedIn()) {
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/login.html.php';
  exit();
}
//clients page
$domainstr = "RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))";
function getDomain($pdo, $id)
{
  $st = $pdo->prepare("SELECT domain FROM client WHERE id=:id");
  $st->bindValue(':id',  $id);
  doPreparedQuery($st, '<h4>Problem</h4>');
  $row = $st->fetch(PDO::FETCH_ASSOC);
  return $row['domain'];
}

if (!$roleplay = userHasWhatRole()) {
  $error = 'Only Account Administrators may access this page!!';
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/accessdenied.html.php';
  exit();
} else {

  list($key, $priv) = $roleplay;
  if ($priv != 'Admin') {
    $error = 'Only Account Administrators may access this page!!';
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/accessdenied.html.php';
    exit();
  }
}

if (isset($_POST['action']) and $_POST['action'] == 'Delete') {
  $id = $_POST['id'];
  $title = "Prompt for deletion";
  $prompt = "Are you sure you want to delete this client? ";
  $call = "confirm";
  $pos = "Yes";
  $neg = "No";
  $action = '';
}

if (isset($_POST['confirm']) and $_POST['confirm'] == 'No') {
  header('Location: . ');
  exit();
}

if (isset($_POST['confirm']) and $_POST['confirm'] == 'Yes') {
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $st = $pdo->prepare("DELETE FROM client WHERE id =:id");
  $st->bindValue(":id", $_POST['id']);
  $res = doPreparedQuery($st, 'Error deleting client.');
  if (!$res) {
    $error = 'Error deleting client.';
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
    exit();
  }
  header('Location: . ');
  exit();
} ////////////END OF DELETE....START OF EDIT


if (isset($_POST['action']) and $_POST['action'] == 'Edit') {
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $sql = "SELECT id, name, domain, tel FROM client WHERE id =:id";
  $st = $pdo->prepare($sql);
  $st->bindValue(":id", $_POST['id']);
  $res = doPreparedQuery($st, 'Error fetching client details.');

  if (!$res) {
    $error = 'Error fetching user details.';
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
    exit();
  }
  $row = $st->fetch(PDO::FETCH_ASSOC);
  $pagetitle = 'Edit Client';
  $action = 'editform';
  $name = $row['name'];
  $domain = $row['domain'];
  $tel = $row['tel'];
  $id = $row['id'];
  $button = 'Update Client';
  include 'form.html.php';
  exit();
}

if (isset($_GET['editform'])) {

  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $dom = getDomain($pdo, $_POST['id']);
  $st = $pdo->prepare("UPDATE client SET name=:nom, domain=:dom, tel=:tel WHERE id=:id");
  $st->bindValue(':nom', $_POST['name']);
  $st->bindValue(':dom', $_POST['domain']);
  $st->bindValue(':tel', $_POST['tel']);
  $st->bindValue(':id',  $_POST['id']);
  $res = doPreparedQuery($st, 'Error updating client.');
  if (!$res) {
    $error = 'Error updating client.';
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
    exit();
  }
  $id = $_POST['id'];
  $newdom = getDomain($pdo, $_POST['id']);

  if ($dom !== $newdom) {
    header("Location: ../admin/?domain=$dom&updated=$newdom");
    exit();
  }
  header('Location: . ');
  exit();
}

if (isset($_GET['add'])) {
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $id = '';
  $pagetitle = 'New Client';
  $action = 'addform';
  $name = '';
  $domain = '';
  $tel = '';
  $button = 'Add Client';
  include 'form.html.php';
  exit();
} //////////////END OF ADD


if (isset($_GET['associate'])) {
  $dom = $_GET['associate'];
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $sql = "SELECT id, name, domain FROM client WHERE domain='$dom'";
  $st = doQuery($pdo, $sql, 'Error fetching id.');
  $row = $st->fetch(PDO::FETCH_ASSOC);
  $clientdom = $row['domain'];
  $clientname = $row['name'];
  $clientid = $row['id'];
  $pos = 'proceed';
  $neg = 'decline';
  $call = "associate";
  $template = "associate.html.php";
}

if (isset($_POST['associate'])) {
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $dom = strtolower($_POST['dom']);
  $_cid = strtolower($_POST['id']);
  $sql = "SELECT id FROM user WHERE $domainstr = '$dom'";
  $st = doQuery($pdo, $sql, 'Error adding client.');
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  foreach ($rows as $row) {
    $id = $row['id'];
    $sql = "UPDATE user SET client_id='$_cid' WHERE id='$id'";
    doQuery($pdo, $sql, 'Error updating user.');
  }
}

if (isset($_GET['addform'])) {
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $dom = $_POST['domain'];
  $sql = "INSERT INTO client (name, domain, tel) VALUES (:nom, :dom, :tel)";
  $st = $pdo->prepare($sql);
  $st->bindValue(':nom', $_POST['name']);
  $st->bindValue(':dom', $dom);
  $st->bindValue(':tel', $_POST['tel']);
  $res = doPreparedQuery($st, 'Error adding client.');
  $cid = lastInsert($pdo);
  //alert required for non unique domains. I attempted to enter uni.com
  if (!$res) {
    $error = 'Error adding client.';
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
    exit();
  }

  $sql = "SELECT id FROM user WHERE $domainstr = '$dom'";
  $st = doQuery($pdo, $sql, 'Error adding client.');
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  //dump($rows);
  if (!empty($rows)) {
    header("Location: ./?associate=$dom");
    exit();
  }

  header('Location: . ');
  exit();
} //end of addform


include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
$sql = "SELECT id, name, domain from client"; // THE DEFAULT QUERY
$cid = 0; //$id MAY have been set by delete so don't overwrite;
if (isset($_POST['act']) and $_POST['act'] == 'Choose'  and $_POST['client'] != '') {
  $cid =  $_POST['client'];
  $sql .= " WHERE id=:id";
}
$sql .= " ORDER BY name";
$st = $pdo->prepare($sql);
if ($cid) {
  $st->bindValue(":id", $cid);
}
doPreparedQuery($st, "<p>Error retrieving clients from database!</p>");
$rows = $st->fetchAll();
if (!$rows) {
  $error = "Error retrieving clients from database!";
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
  exit();
}

foreach ($rows as $row) {
  $clients[] = array(
    'id' => $row['id'],
    'name' => $row['name'],
    'domain' => $row['domain']
  );
}

include 'clients.html.php';
