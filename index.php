<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/magicquotes.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/access.inc.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/uploads/includes/helpers.inc.php';

$base = 'Log In';
$error = '';
$tmpl_error = '/uploads/includes/error.html.php';
$myip = '86.133.121.115.';
function getRemoteAddr(){
    $ipAddress = $_SERVER['REMOTE_ADDR'];
if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
    $ipAddress = array_pop(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
}
    return $ipAddress;
}

$mefiles = function($arg){
    return $_FILES['upload'][$arg];
};
if(isset($_GET['action']) && $_GET['action'] == 'download') {;}
else{
//include $_SERVER['DOCUMENT_ROOT'] . '/uploads/templates/base.html.php';
}

if (!userIsLoggedIn()){
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/templates/base.html.php';
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/templates/login.html.php';
exit();
}
//public page
if (!$roleplay=userHasWhatRole()){
$error = 'Only valid clients may access this page.';
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/templates/accessdenied.html.php';
exit();// endof OBTAIN access level
}
else {
foreach ($roleplay as $key => $priv){// $roleplay is an array, use foreach to obtain value and index
}
$domain="RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))";//!!?!! V. USEFUL VARIABLE IN GLOBAL SPACE
}


if (isset($_POST['action']) and $_POST['action'] == 'upload'){ 
    //Bail out if the file isn't really an upload
if (!is_uploaded_file($_FILES['upload']['tmp_name'])){
$error = 'There was no file uploaded!';
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/error.html.php';
exit();
}

$uploadfile = $mefiles('tmp_name');
$realname = $mefiles('name');
$ext = preg_replace('/(.*)(\.[^0-9.]+$)/i', '$2', $realname);

$time = time();
$uploadname = $time . getRemoteAddr() .$ext;

$path = '../../filestore/';  
$filedname =  $path . $uploadname;
// Copy the file (if it is deemed safe)
if (!copy($uploadfile, $filedname)){ 
$error = "Could not  save file as $filedname!";
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/error.html.php';
exit();
}
echo $key;
if($priv=='Admin' and !empty($_POST['user'])){//ie Admin selects user
$key=$_POST['user'];
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/db.inc.php';
$sql="SELECT domain FROM client WHERE domain='$key'";//will either return empty set(no error) or produce count. Test to see if a client has been selected.
$row = doSafeFetch($link, $sql);
    
if(count($row[0])>0){
$sql="SELECT employer.user_name, employer.user_id FROM (SELECT user.name AS user_name, user.id AS user_id, client.domain FROM user INNER JOIN client ON $domain =client.domain) AS employer WHERE employer.domain='$key' LIMIT 1";//RETURNS one user, as relationship between file and user is one to one.
//exit($sql);
$row = doSafeFetch($link, $sql);
$key=$row['user_id'];
if(!$key) {
$key=$_POST['user'];//$key will be empty if above query returned empty set, reset
$sql="SELECT user.id from user INNER JOIN client ON user.client_id=client.id WHERE user.email='$key'";
$row = doSafeFetch($link, $sql);
$key=$row['id'];
}// @ clients use domain or full email as key if neither tests produce a result key refers to a user only
}//END OF COUNT
}

// Prepare user-submitted values for safe database insert
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/db.inc.php';
$realname = doSanitize($link,  $realname);
$size = doSanitize($link, $mefiles('size'));
$uploadname = doSanitize($link, $uploadname);
$uploadtype = doSanitize($link, $mefiles('type'));
$uploaddesc = doSanitize($link,  isset($_POST['desc']) ? $_POST['desc'] : '');
$path = doSanitize($link,  $path);
$time = doSanitize($link,  $time);
    
$sql = "INSERT INTO upload SET
filename = '$realname',
mimetype = '$uploadtype',
description = '$uploaddesc',
filepath = '$path',
file = '$uploadname',
size ='$size'/1024,
userid='$key',
time=NOW()";
doFetch($link, $sql, 'Database error storing file information!' . $sql);
/*$sql2 = "select user.whatever from user INNER JOIN upload ON user.id=upload.userid INNER JOIN (SELECT MAX(id) AS big FROM upload) AS last ON last.big = upload.id";
NOT REQUIRED - USING mysqli_INSERT_ID INSTEAD - BUT KEPT AS AN EXAMPLE OF A SUBQUERY
*/
$menum = mysqli_insert_id($link);
$sql = "select user.email, user.name, upload.id, upload.filename from user INNER JOIN upload ON user.id=upload.userid WHERE upload.id=$menum";
    
doFetch($link, $sql, 'Error selecting email address.');

$row = doSafeFetch($link, $sql);
$email = $row['email'];
$file = $row['filename'];
$name = $row['name'];

if($priv=='Admin'){
$body =  'We have just uploaded the file' . $file . 'for checking.'; 
$body = wordwrap($body, 70);
//mail($email, $file, $body, "From: $name <{$_SESSION['email']}>");
}

/*
else {
$body =  '<html><body><p>We have just uploaded the file <a href='.
'"http://northwolds.serveftp.net/uploads/" /><strong>' . $file . '</strong></a> for printing.</p></body></html>'; 
if (!@mail('north.wolds@btinternet.com', 'Files to North Wolds | ' . $file,  
   $body,  
    "From: $name <{$_SESSION['email']}>\n" . 
     "cc:  $name <files@northwolds.co.uk>\n" .
    "MIME-Version: 1.0\n" .  
    "Content-type: text/html; charset=iso-8859-1"))
{
 exit('<p>The file uploaded but an email could not be sent.</p>');  
}
}
*/
header('Location: .');
exit();
}// end of upload_____________________________________________________________________


if (isset($_GET['action']) and isset($_GET['id'])){
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/db.inc.php';
$id = doSanitize($link,  $_GET['id']);
$sql = "SELECT filename, mimetype, filepath, file, size FROM upload WHERE id = '$id'";
$result = mysqli_query($link, $sql);
if (!$result){
$error = 'Database error fetching requested file.';
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/error.html.php';
exit();
}
$file = mysqli_fetch_array($result);
if (!$file)
{
$error = 'File with specified ID not found in the database!';
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/error.html.php';
exit();
}
$filename = $file['filename'];
$mimetype = $file['mimetype'];
$filepath = $file['filepath'];
$uploadfile = $file['file'];
$size = $file['size'];
$filepath .= $uploadfile;
$fullpath = $_SERVER['DOCUMENT_ROOT'] . $filepath;
$filedata = file_get_contents($fullpath);
$disposition = 'inline';
$ext = preg_replace('/(.*)(\.[^0-9.]+$)/i', '$2', $filename);
//$mimetype = 'application/x-unknown'; application/octet-stream

if ($_GET['action'] == 'download'){
$disposition = 'attachment';
}
//Content-type must come before Content-disposition
header("Content-type: $mimetype");
header('Content-disposition: ' . $disposition . '; filename='.'"'.$filename.'"');//this works
//header("Content-Transfer-Encoding: binary");
header('Content-length:' . strlen($filedata));
echo $filedata;
exit();
}// end of download

if (isset($_POST['action']) and $_POST['action'] == 'delete' ) {
$id = $_POST['id'];
$title="Prompt";
$prompt = "Are you sure you want to delete this file? ";
$call ="confirm";
$pos="Yes";
$neg="No";
$action =''; 
}

if (isset($_POST['confirm']) and $_POST['confirm'] == 'Yes' ){
$prompt = "Select the extent of deletions";
$id = $_POST['id'];
$del ="proceed";
}

if (isset($_POST['proceed']) and $_POST['proceed'] == 'remove' ){
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/db.inc.php';
$id = doSanitize($link, $_POST['id']);

$path = '../../filestore/';  

if($_POST['extent']=="c"){
$sql = "SELECT c.file FROM user INNER JOIN client ON user.client_id = client.id INNER JOIN upload AS c ON user.id = c.userid  INNER JOIN upload AS d ON d.userid=user.id WHERE d.id=$id";
}
elseif($_POST['extent']=="u"){
$sql="SELECT upload.file FROM upload INNER JOIN user ON upload.userid=user.id INNER JOIN upload AS d ON upload.userid=d.userid WHERE d.id=$id";
}
elseif($_POST['extent']=="f") {
$sql = "SELECT file FROM upload WHERE id=$id";
}
else {
header('Location: .');
exit();
}


$result = mysqli_query($link, $sql);

//exit($sql);

if (!$result){
$error = 'Database error fetching stored files.';
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/error.html.php';
exit();
}

while ($row = mysqli_fetch_array($result)){
$file = $row['file'];
$sql = "DELETE FROM upload WHERE file = '$file'";
if(!mysqli_query($link, $sql)) {//delete file ref
$error = 'Error deleting file.';
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/error.html.php';
exit();
}
$thepath=$path.$file;
unlink($thepath);
}
header('Location: .');
exit();
}//________________________end of confirm/delete

if (isset($_POST['confirm']) and $_POST['confirm'] == 'No'){//swap
$prompt = "Change ownership on ALL files?";
$id = $_POST['id'];
$swap ="swap";
$call ="swap";
$pos="Yes";
$neg="No";
$action = '';
}

if (isset($_POST['swap'])) {//SWITCH OWNER OF FILE OR JUST UPDATE DESCRIPTION (FILE AMEND BLOCK)
$colleagues = '';
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/db.inc.php';
$id = doSanitize($link,  $_POST['id']);
$answer = $_POST['swap'];
$email="{$_SESSION['email']}";

if ($priv=='Admin') {
$sql = "SELECT upload.id, filename, description, upload.userid, user.name FROM upload INNER JOIN user ON upload.userid=user.id  WHERE upload.id=$id";
$result = mysqli_query($link, $sql);
if (!$result){
$error = 'Database error fetching stored files.';
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/error.html.php';
exit();
}
$row = mysqli_fetch_array($result);
$id = $row['id'];
$filename = $row['filename'];
$diz = $row['description'];
$userid = $row['userid'];
$aname = $row['name'];
$button = "Update";
$action ='';
$answer = $_POST['swap'];

$sql_col = "SELECT employer.id, employer.name FROM upload INNER JOIN user ON upload.userid = user.id INNER JOIN (SELECT user.id, user.name, client.domain FROM user INNER JOIN client ON $domain=client.domain) AS employer ON $domain=employer.domain WHERE upload.id=$id ORDER BY name";//colleagues
//exit($sql_col);
$result = mysqli_query($link, $sql_col);
if (!$result)
{
$error = 'Database error fetching colleagues.';
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/error.html.php';
exit();
}

while ($row = mysqli_fetch_array($result)){
$colleagues[$row['id']] = $row['name'];
}
if(count($colleagues)==0){
$sql="SELECT user.name, user.id FROM user LEFT JOIN client ON user.client_id=client.id  WHERE client.domain IS NULL UNION SELECT user.name, user.id FROM user INNER JOIN client ON user.client_id=client.id ORDER BY name";
$result = mysqli_query($link, $sql);
if (!$result){
$error = 'Database error fetching users.';
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/error.html.php';
exit();
}
while ($row = mysqli_fetch_array($result)){
$all_users[$row['id']] = $row['name'];
}
}
}//if
else {
header('Location: . ');
exit();
}
}///


if (isset($_POST['original'])) {//CAN ONLY BE SET BY ADMIN, 'original' is common to both options of file amend block
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/db.inc.php';

$fid = doSanitize($link,  $_POST['fileid']);
$fname = doSanitize($link,  $_POST['filename']);
$orig = doSanitize($link,  $_POST['original']);
$user = doSanitize($link,  $_POST['user']);
if($_POST['colleagues']){
$user = doSanitize($link,  $_POST['colleagues']);
}
$diz = doSanitize($link,  $_POST['description']);
if(!$user) {
    $user = $orig;
}
if($_POST['answer']=='Yes') {
    $sql="UPDATE upload SET userid='$user' WHERE userid='$orig'";
}
else {
    $sql="UPDATE upload SET userid='$user', description='$diz', filename='$fname' WHERE id ='$fid'";
}
if (!mysqli_query($link, $sql)) {
$error = 'error updating details';
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/error.html.php';
exit();
}
header('Location: . ');
exit();
}
///end of F I L E AMEND BLOCK___________________________________________________________________

//a default block___________________________________________________________________

$display=10;
if (isset($_GET['p']) and is_numeric($_GET['p'])){
$pages=$_GET['p'];
}
else { // counts all files
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/db.inc.php';
$sql = "SELECT COUNT(upload.id) from upload ";
if ($priv =='Client' ){
$email=$_SESSION['email'];
$sql .= " INNER JOIN user on upload.userid = user.id WHERE user.email='$email'";
}
$r = mysqli_query($link, $sql);
if (!$r)
{
$error = 'Database error fetching requesting THE list of files.';
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/error.html.php';
exit();
}
$row = mysqli_fetch_array($r, MYSQL_NUM);
$records = $row[0];
if ($records > $display) {
$pages = ceil($records/$display);
}
else $pages =1;//INITIAL SETTING OF PAGES
}//end of IF NOT PAGES SET

if (isset($_GET['s']) and is_numeric($_GET['s'])){
$start=$_GET['s'];
}
else {
$start = 0;
}

$meswitch = array( 'f' =>'filename ASC', 'ff' =>'filename DESC', 'u' =>'user ASC', 'uu' =>'user DESC', 'uf' =>'user ASC, filename ASC', 'uuf' =>'user DESC, filename ASC',  'uff' =>'user ASC, filename DESC',  'uuff' =>'user DESC, filename DESC', 'ut' =>'user ASC, time ASC', 'utt' =>'user ASC, time DESC', 'uut' =>'user DESC, time ASC', 'uutt' =>'user DESC, time DESC', 't' =>'time ASC', 'tt' =>'time DESC');
$sort = (isset($_GET['sort']) ? $_GET['sort'] : '1');
foreach ($meswitch as $ix => $u){
if ($ix == $sort) break;
}
switch ($sort) {
case $ix : 
$order_by = $meswitch[$ix];
break;
default:
$order_by = 'time DESC';
$sort = 'tt';
break;
}
//D I S P L A Y_______________________________________________________________
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/db.inc.php';///Present list of users for administrators
$sqlu ="SELECT user.id, user.name FROM user LEFT JOIN client ON user.client_id=client.id WHERE client.domain IS NULL ORDER BY name";
$result = mysqli_query($link, $sqlu);
if (!$result)
{
$error = 'Database error fetching users.';
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/error.html.php';
exit();
}
while ($row = mysqli_fetch_array($result)){
$users[$row['id']]=$row['name'];
}
/*$sqlc ="SELECT employer.user_id, employer.name from
(SELECT user.name, user.id as user_id, client.domain FROM user INNER JOIN client ON RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))=client.domain) AS employer";*/

$sqlc ="SELECT name, domain, tel FROM client ORDER BY name";
$result = mysqli_query($link, $sqlc);
if (!$result)
{
$error = 'Database error fetching clients.';
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/error.html.php';
exit();
}
while ($row = mysqli_fetch_array($result)){
$client[$row['domain']]=$row['name'];
}
//end of default_______________________________________________________________________



if (isset($_GET['find'])) {
if($priv != "Admin")://CUSTOMISES SELECT MENU
$email="{$_SESSION['email']}";
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/db.inc.php';
$sql="SELECT $domain  FROM user WHERE user.email='$email'";
$result = mysqli_query($sql);
$row = mysqli_fetch_array($result);
$dom=$row[0];
$sql="SELECT COUNT(*) AS dom FROM user INNER JOIN client ON $domain=client.domain WHERE $domain='$dom' AND client.domain='$dom'";
$result = mysqli_query($sql);
$row = mysqli_fetch_array($result);
$count=$row['dom'];
if(count($count) > 0) {
$where =" WHERE user.email='$email'";//client
}
else {
$where =" WHERE user.id=$key";//user
}
$sql="SELECT employer.id, employer.name  FROM user INNER JOIN (SELECT user.id, user.name, client.domain FROM user INNER JOIN client ON $domain=client.domain) AS employer ON $domain=employer.domain $where";
$result = mysqli_query($link, $sql);
if (!$result)
{
$error = 'Database error fetching clients.';
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/error.html.php';
exit();
}
$users=array();//resets user array to display users of current client
while ($row = mysqli_fetch_array($result)){
$users[$row['id']]=$row['name'];
}
if($count<=1){//SELECT MENU in SEARCH for only more than one "employee"
$users=array();
$zero=true;
}
$client= array();
endif;
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/templates/base.html.php';
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/templates/search.html.php';
exit();
}
/// S E A R C H  M E !!

//INITIAL FILE SELECTION
//_______//_______//_______//_______//_______//_______//_______//_______//_______//_____
$select = "SELECT upload.id, filename, mimetype, description, filepath, file, size, time,  MID(file, 11, 14) AS origin, user.email";
$from =" FROM upload INNER JOIN user ON upload.userid=user.id";
$order=" ORDER BY $order_by LIMIT $start, $display";
$domain="RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))";
//_______//_______//_______//_______//_______//_______//_______//_______//_______//_____

if (isset($_GET['action']) and $_GET['action'] == 'search'){
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/db.inc.php';
$tel = '';
$from .=" INNER JOIN userrole ON user.id=userrole.userid";
$user_id =  doSanitize($link, $_GET['user']);
if ($priv =='Admin' ){
$sql="SELECT domain FROM client WHERE domain='".$user_id."'";//will either return empty set(no error) or produce count. Test to see if a client has been selected.
$result = mysqli_query($link, $sql);
$row = mysqli_fetch_array($result);
if(count($row[0])> 0 and  !is_numeric($user_id)) {//user_id is text(domain) for Clients
$from .=" INNER JOIN client ON $domain =client.domain ";
$where = " WHERE domain='".$user_id."'";
$check = count($row[0]);
}
else{ $where = ' WHERE TRUE'; }
$select .= ", user.name as user";
}//admin
else {
$email=$_SESSION['email'];
$where .=" WHERE user.email='$email' ";
}
if ($user_id != '') { // A user is selected 
if(!isset($check)) $where .= " AND user.id=$user_id";
}
$text = doSanitize($link, $_GET['text']);
if ($text != '') { // Some search text was specified 
$where .= " AND upload.filename LIKE '%$text%'";
}
$suffix= doSanitize($link, $_GET['suffix']);
if (isset($suffix)){ 
if($suffix =='owt'){
$where .= " AND (upload.filename NOT LIKE '%pdf' AND upload.filename NOT LIKE '%zip')"; 
}
elseif($suffix =='pdf' or $suffix =='zip') {
	$where .= " AND upload.filename LIKE '%$suffix'"; 
	//$where .= sprintf(" AND upload.filename LIKE %s", GetSQLValueString('%'.$suffix, "text"));//Tricky percent symbol
}
}

$sql =  $select . $from . $where . $order;

$result = mysqli_query($link, $sql); 
 if (!$result)  {   
$error = 'Error fetching file details1.' . $sql ; 
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/error.html.php';
 exit();
}
$sqlcount = $select . ', COUNT(upload.id) as total ' . $from . $where . $order;
//exit($sqlcount);
$r = mysqli_query($link, $sqlcount); 
 if (!$r)  {   
$error = 'Error getting file count.'; 
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/includes/error.html.php';
exit();
}
$row = mysqli_fetch_array($r);
$records = $row['total'];
if ($records > $display) {
$pages = ceil($records/$display);
}
else $pages =1;

$files = array();
while ($row = mysqli_fetch_array($result)){
$files[] = array(
'id' => $row['id'],
'user' => $row['user'],
'email'=>$row['email'],
'filename' => $row['filename'],
'mimetype' => $row['mimetype'],
'description' => $row['description'],
'filepath' => $row['filepath'],
'file' => $row['file'],
'origin' => $row['origin'],
'time' => $row['time'],
'size' => $row['size']);
}
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/templates/base.html.php';
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/templates/files.html.php';
exit();
}
//ENDEND S E A R C H//ENDEND S E A R C H//ENDEND S E A R C H//ENDEND S E A R C H

if ($priv =='Admin' ){
$select .= ", user.name as user";//append to line 465(ish)
$from .=" INNER JOIN userrole ON user.id=userrole.userid";
$where  = ' WHERE TRUE';
if(isset($_GET['ext']) && $ext=doSanitize($link, $_GET['ext'])) {
if($ext =='owt') {
$where .= " AND (upload.filename NOT LIKE '%pdf' AND upload.filename NOT LIKE '%zip')"; 
}
else $where .=" AND upload.filename LIKE '%$ext'";
}
if( isset($useroo) && is_numeric($useroo)){//CLIENTS USE EMAIL DOMAIN AS ID THERFORE NOT A NUMBER
if($useroo=doSanitize($_GET['u'])) $where .= " AND user.id=$useroo";
}
else {
if(isset($_GET['u']) && $useroo=doSanitize($link, $_GET['u'])) $where .= " AND $domain='$useroo'";
}
if(isset($_GET['t'])  && $textme=doSanitize($link, $_GET['t'])) $where .= " AND upload.filename LIKE '%$textme%'";
}//admin

else {
$email=$_SESSION['email'];
$from .=" INNER JOIN userrole ON user.id=userrole.userid";
//$where .=" WHERE user.email='$email' ";
$where =" WHERE user.email='$email' ";
}

//$sql= $select . $from . $where . $order; //DEFAULT; TELEPHONE BLOCK REQUIRED TO OBTAIN CLIENT PHONE NUMBER
$sql = $select;
$select_tel = ", client.tel";
$from .=" LEFT JOIN client ON user.client_id=client.id";//note LEFT join to include just 'users' also
$sql .= $select_tel . $from. $where . $order;
//____________________________________________________________________________________________END OF TELEPHONE

$result = doFetch($link, $sql, 'Database error fetching files. ' . $sql);

$files = array();
while ($row = mysqli_fetch_array($result)){
$files[] = array(
'id' => $row['id'],
'user' => (isset($row['user'])) ? $row['user'] :'',
'email'=> $row['email'],
'filename' => $row['filename'],
'mimetype' => $row['mimetype'],
'description' => $row['description'],
'filepath' => $row['filepath'],
'file' => $row['file'],
'origin' => $row['origin'],
'time' => $row['time'],
'tel' => $row['tel'],// ONLY REQUIRED FOR TELEPHONE BLOCK
'size' => $row['size']);
}
$base = 'North Wolds Printers | The File Uploads';
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/templates/base.html.php';
include $_SERVER['DOCUMENT_ROOT'] . '/uploads/templates/files.html.php';
?>