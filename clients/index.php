<?php
/*mysqli_real_escape_string\(([^,]+),([^)]+\);)
mysqli_real_escape_string($2, $1);*/
require_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/access.inc.php';
if (!userIsLoggedIn()){
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/login.html.php';
exit();
}
//clients page

if (!$roleplay=userHasWhatRole()){
$error = 'Only Account Administrators may access this page!!';
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/accessdenied.html.php';
exit();
}
else {
foreach ($roleplay as $key => $priv){// $roleplay is an array, use foreach to obtain value and index
}
if($priv!='Admin'){
$error = 'Only Account Administrators may access this page!!';
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/accessdenied.html.php';
exit();
}
}


if (isset($_POST['action']) and $_POST['action'] == 'Delete') {
$id = $_POST['id'];
$title="Prompt for deletion";
$prompt = "Are you sure you want to delete this client? ";
$call ="confirm";
$pos="Yes";
$neg="No";
$action =''; 
//include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/prompt.html.php';
//exit(); 
}

if (isset($_POST['confirm']) and $_POST['confirm'] == 'No') {
header('Location: . ');
exit();
}

if (isset($_POST['confirm']) and $_POST['confirm'] == 'Yes') {
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
$id = mysqli_real_escape_string($link, $_POST['id']);
$result = mysqli_query($link, "DELETE FROM client WHERE id = $id");
if (!$result){
echo mysqli_errno($link) . ": " . mysqli_error($link) . "\n";
$error = 'Error deleting client.';
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
exit(); 
}
header('Location: . ');
exit();
}////////////END OF DELETE....START OF EDIT


if (isset ($_POST['action']) and $_POST['action'] == 'Edit'){ 
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
$id = mysqli_real_escape_string($link, $_POST['id']);
$sql= "SELECT id, name, domain, tel FROM client WHERE id =$id";
$result = mysqli_query($link, $sql);
if(!$result) {
$error = 'Error fetching user details.';
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
exit();
}

$row = mysqli_fetch_array($result);
$pagetitle = 'Edit Client';
$action = 'editform';
$name = $row['name'];
$domain = $row['domain'];
$tel= $row['tel'];
$id = $row['id'];
$button='Update Client';

include 'form.html.php';
exit();
}

if (isset($_GET['editform']))
{
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
$id = mysqli_real_escape_string($link, $_POST['id']);
$name = mysqli_real_escape_string($link, $_POST['name']);
$domain = mysqli_real_escape_string($link, $_POST['domain']);
$tel = isset($_POST['tel']) ? mysqli_real_escape_string($link, $_POST['tel']) : '';
$sql = "UPDATE client SET name='$name', domain='$domain', tel='$tel' WHERE id=$id";
if (!mysqli_query($link, $sql)) {
$error = 'bloody client details.';
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
exit();
}

header('Location: . ');
exit();
}

if (isset($_GET['add'])){
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
$id='';
$pagetitle = 'New Client';
$action = 'addform';
$name = '';
$domain = '';
$tel = '';
$button='Add Client';
include 'form.html.php';
exit();
}//////////////END OF ADD

if (isset($_GET['addform'])) {
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';

$name = mysqli_real_escape_string($link, $_POST['name']);
$domain= mysqli_real_escape_string($link, $_POST['domain']);
$tel = isset($_POST['tel']) ? mysqli_real_escape_string($link, $_POST['tel'])  : '';

//$sql= "INSERT INTO client SET name='$name', domain='$domain', tel='$tel'";
$sql= "INSERT INTO client VALUES ('?', '$name', '$domain', '$tel')";

//alert required for non unique domains. I attempted to enter uni.com
if(!mysqli_query($link, $sql)) {
echo mysqli_errno($link) . ": " . mysqli_error($link). "\n";
$error = 'Error adding client.';
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
exit();
}
header('Location: . ');
exit();
}//end of addform


include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
$sql = "SELECT id, name, domain from client"; // THE DEFAULT QUERY

if (isset($_POST['act']) and $_POST['act'] == 'Choose'  and $_POST['client'] !=''){
$id =  mysqli_real_escape_string($link, $_POST['client']);
$sql .=" WHERE id=$id";
}

$sql .= " ORDER BY name";

//display clients
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
$result = mysqli_query($link, $sql);
if (!$result ) {
echo mysqli_errno($link) . ": " . mysqli_error($link). "\n";
$error = "Error retrieving clients from database!";
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
  exit();
  }
//$clients = array();
while ($row = mysqli_fetch_array($result))
{
$clients[] = array(
'id' => $row['id'],
'name' => $row['name'],
'domain'=>$row['domain']
);
}
include 'clients.html.php';
?>