<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/access.inc.php';
if (!userIsLoggedIn()) {
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/login.html.php';
  exit();
}

function getDomain($pdo, $id)
{
  $st = $pdo->prepare("SELECT domain FROM client WHERE id=:id");
  $st->bindValue(':id',  $id);
  doPreparedQuery($st, '<h4>Problem</h4>');
  $row = $st->fetch(PDO::FETCH_ASSOC);
  return $row['domain'];
}

$domainstr = "RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))";
$pagetitle = "Manage Clients";
$selected = null;
list($key, $priv) = userHasWhatRole();

if ($priv !== 'Admin') {
  $error = 'Only Account Administrators may access this page!';
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/accessdenied.html.php';
  exit();
}

if (isset($_POST['confirm'])) {
  if ($_POST['confirm'] == 'Yes') {
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
    $st = $pdo->prepare("DELETE FROM client WHERE id =:id");
    $st->bindValue(":id", $_POST['id']);
    $res = doPreparedQuery($st, 'Error deleting client.');
  }
  header('Location: . ');
  exit();
}
////////////END OF DELETE....START OF EDIT

if (isset($_POST['action']) && $_POST['action'] == 'Edited' || isset($_GET['dom'])) {
  if (isset($_POST['delete'])) {
    $id = $_POST['id'];
    $title = "Prompt for deletion";
    $template = 'prompt.html.php';
    $prompt = "Are you sure you want to delete this client? ";
    $call = "confirm";
    $pos = "Yes";
    $neg = "No";
    $action = '';
  } else {
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
}

if (isset($_GET['add'])) {
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $id = '';
  $pagehead = 'New Client';
  $action = 'addform';
  $route = 'Added';
  $name = '';
  $domain = '';
  $tel = '';
  $id = '';
  $button = 'Add Client';
  $pagetitle = 'Admin | Client';
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
  $clientid = lastInsert($pdo);
  //alert required for non unique domains. I attempted to enter uni.com
  if (!$res) {
    $error = 'Error adding client.';
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
    exit();
  }

  $sql = "SELECT id FROM user WHERE $domainstr = '$dom'";
  $st = doQuery($pdo, $sql, 'Error adding client.');
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  if (!empty($rows)) {
    header("Location: ./?associate=$dom");
    exit();
  }
  header('Location: . ');
  exit();
} //end of addform

/// DEFAULT /////
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
$sql = "SELECT id, name, domain from client"; // THE DEFAULT QUERY
//$cid = 0; //$id MAY have been set by delete so don't overwrite;

if (isset($_POST['action']) && $_POST['action'] == 'Choose' && $_POST['client'] != '') {
  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
  $selected = true;
  $sql = "SELECT id, name, domain, tel FROM client WHERE id =:id";
  $id = $_POST['client'];
  $st = $pdo->prepare($sql);
  $st->bindValue(":id", $id);
  $res = doPreparedQuery($st, 'Error fetching client details.');
  $row = $st->fetch(PDO::FETCH_ASSOC);
  $pagehead = 'Edit Client';
  $action = 'editform';
  $route = 'Edited';
  $name = $row['name'];
  $domain = $row['domain'];
  $tel = $row['tel'];
  $button = 'Update Client';
  include 'form.html.php';
  exit();
  $clientid = $id;
  $sql .= " WHERE id=:id";
}
$sql .= " ORDER BY name";
$st = $pdo->prepare($sql);
if (isset($clientid)) {
  $st->bindValue(":id", $clientid);
}
doPreparedQuery($st, "Error retrieving clients from database!");
$rows = $st->fetchAll();
$clients = [];

foreach ($rows as $row) {
  $clients[] = array(
    'id' => $row['id'],
    'name' => $row['name'],
    'domain' => $row['domain']
  );
}
include 'clients.html.php';
