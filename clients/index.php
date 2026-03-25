<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/access.inc.php';

if (!userIsLoggedIn()) {
  $pagetitle = "Log In";
  include TEMPLATE . 'login.html.php';
  exit();
}

$predicates = [partial('preg_match', '/^nwp/')];
$calltext = "Add New Client";
$callroute = 'add';
$pageid = 'admin_client';

function getDomain($pdo, $id)
{
  $st = $pdo->prepare("SELECT domain FROM client WHERE id=:id");
  $st->bindValue(':id',  $id);
  doPreparedQuery($st, 'Problem finding domain');
  $row = $st->fetch(PDO::FETCH_ASSOC);
  return $row['domain'];
}

$nwpdomainstr = fromStrPos(DBSYSTEM);
$pagetitle = "Manage Clients";
$selected = null;
list($key, $priv) = obtainUserRole(true);

if ($priv !== 'Admin') {
  $e = 'Only Account Administrators may access this page!';
  header("Location: ../?loginerror=$e");
  exit();
}

if (isset($_GET['delete'])) {
  $id = $_GET['delete'];
  $title = "Prompt for deletion";
  $prompt = "Are you sure you want to delete this client? ";
  $call = "confirm";
  $pos = "Yes";
  $neg = "No";
  $action = '?';
  $formname = 'deleteclientform';
  $template = 'confirm.html.php';
}

if (isset($_POST['confirm'])) {
  if ($_POST['confirm'] == 'Yes' && isset($_POST['id'])) {
    include CONNECT;
    $nwpst = $pdo->prepare("DELETE FROM client WHERE id =:id");
    $nwpst->bindValue(":id", $_POST['id']);
    doPreparedQuery($nwpst, 'Error deleting client.');
  }
  header('Location: .');
  exit();
}

if (isset($_POST['action']) && ($_POST['action'] == 'Edited' || isset($_GET['dom']))) {
  include CONNECT;
  $dom = getDomain($pdo, $_POST['id']);
  $nwpst = $pdo->prepare("UPDATE client SET name=:nom, domain=:dom, tel=:tel WHERE id=:id");
  $nwpst->bindValue(':nom', $_POST['name']);
  $nwpst->bindValue(':dom', $_POST['domain']);
  $nwpst->bindValue(':tel', $_POST['tel']);
  $nwpst->bindValue(':id',  $_POST['id']);
  $res = doPreparedQuery($nwpst, 'Error updating client.');
  if (!$res) {
    $error = 'Error updating client.';
    include TEMPLATE . 'error.html.php';
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
  include CONNECT;
  $pagehead = 'New Client';
  $action = 'addform';
  $route = 'Added';
  $button = 'Add Client';
  $pagetitle = 'Admin | Client';
  include 'form.html.php';
  exit();
}

if (isset($_GET['associate'])) {
  $dom = $_GET['associate'];
  include CONNECT;
  $nwpst = $pdo->prepare("SELECT id, name, domain FROM client WHERE domain=:dom");
  $nwpst->bindValue(":dom", $dom);
  doPreparedQuery($nwpst, 'Error fetching id.');
  $nwprow = $nwpst->fetch(PDO::FETCH_ASSOC);
  $clientdom = $nwprow['domain'];
  $clientname = $nwprow['name'];
  $clientid = $nwprow['id'];
  $pos = 'proceed';
  $neg = 'decline';
  $call = "associate";
  $template = "associate.html.php";
}

if (isset($_POST['associate'])) {
  include CONNECT;
  $dom = strtolower($_POST['dom']);
  $nwpclientID = strtolower($_POST['id']);
  $nwpst = $pdo->prepare("SELECT id FROM usr WHERE $nwpdomainstr=:dom");
  $nwpst->bindValue(":dom", $dom);
  doPreparedQuery($nwpst, 'Error fetching id.');
  $nwprows = $nwpst->fetchAll(PDO::FETCH_ASSOC);
  foreach ($nwprows as $nwprow) {
    $id = $nwprow['id'];
    $nwpst = $pdo->prepare("UPDATE usr SET client_id=:cid WHERE id=:id");
    $nwpst->bindValue(":id", $id);
    $nwpst->bindValue(":cid", $nwpclientID);
    doPreparedQuery($nwpst, 'Error updating user.');
  }
}
if (isset($_POST['action']) && $_POST['action'] === 'Added') {
  include CONNECT;
  $dom = $_POST['domain'];
  $nwpst = $pdo->prepare("INSERT INTO client (name, domain, tel) VALUES (:nom, :dom, :tel)" );
  $nwpst->bindValue(':nom', $_POST['name']);
  $nwpst->bindValue(':dom', $dom);
  $nwpst->bindValue(':tel', $_POST['tel']);
  $res = doPreparedQuery($nwpst, 'Error adding client.');
  $clientid = lastInsert($pdo, DBSYSTEM, 'client');
  //alert required for non unique domains. I attempted to enter uni.com
  if (!$res) {
    $error = 'Error adding client.';
    include TEMPLATE . 'error.html.php';
    exit();
  }
  $nwpst = $pdo->prepare("SELECT id FROM usr WHERE $nwpdomainstr =:dom");
  $nwpst->bindValue(':dom', $dom);
  doPreparedQuery($nwpst, 'Error selecting client by domain.');
  $nwprows = $nwpst->fetchAll(PDO::FETCH_ASSOC);
  if (!empty($nwprows)) {
    header("Location: ./?associate=$dom");
    exit();
  }
  header('Location: . ');
  exit();
} //end of addform

/// DEFAULT /////
include CONNECT;
$nwpsql = "SELECT id, name, domain from client"; // THE DEFAULT QUERY

if (isset($_POST['action']) && $_POST['action'] === 'Choose' && $_POST['client'] !== '') {
  include CONNECT;
  $selected = true;
  $id = $_POST['client'];
  $nwpst = $pdo->prepare("SELECT id, name, domain, tel FROM client WHERE id =:id");
  $nwpst->bindValue(":id", $id);
  doPreparedQuery($nwpst, 'Error fetching client details.');
  $nwprow = $nwpst->fetch(PDO::FETCH_ASSOC);
  $name = $nwprow['name'];
  $domain = $nwprow['domain'];
  $tel = $nwprow['tel'];
  $pagehead = 'Edit Client';
  $action = 'editform';
  $route = 'Edited';
  $calltext = "Delete Client";
  $callroute = "delete=$id";
  $button = 'Update Client';
  include 'form.html.php';
  exit();
  $clientid = $id;
  $nwpsql .= " WHERE id=:id";
}
$nwpsql .= " ORDER BY name";
$nwpst = $pdo->prepare($nwpsql);
if (isset($clientid)) {
  $nwpst->bindValue(":id", $clientid);
}
doPreparedQuery($nwpst, "Error retrieving clients from database!");
$nwprows = $nwpst->fetchAll();
$clients = [];

foreach ($nwprows as $nwprow) {
  $clients[] = array(
    'id' => $nwprow['id'],
    'name' => $nwprow['name'],
    'domain' => $nwprow['domain']
  );
}

/*
$ql = "SELECT SUBSTRING(usr.name, LENGTH(usr.name) - LOCATE(' ', REVERSE(usr.name)) +2) AS brill FROM usr WHERE id = 53";
$ql = "SELECT SUBSTRING(usr.name, LOCATE(' ', usr.name) +1) AS user FROM usr WHERE id=53";
*/
include 'clients.html.php';
