<?php 

function getKey($priv, $user){
    if($priv !='Admin' or empty($user)){
        return $key;
    }
    
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

    
}

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