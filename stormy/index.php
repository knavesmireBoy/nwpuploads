<?php
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
if (isset($_POST['action']) and $_POST['action'] == 'split'){
$length = count($_POST)+1;
for ($i=0; $i < $length; $i++) {
if(!$_POST[$i]) continue;
$letters=$_POST[$i];
foreach($letters as $letter){
$sql = "INSERT INTO fchar SET nameid=$i, letter='$letter' ";
$result = mysql_query($sql, $linkst);
if (!$result) {
$error = "Error adding letters to fchar!" . mysql_error();
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
exit();
}
}
}
header('Location: .');
exit();
}


include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
$sql="SELECT fchar.letter, fname.id from fchar INNER JOIN fname ON fchar.nameid = fname.id";
$result = mysql_query($sql, $linkst);
if (!$result ) {
$error = "Error retrieving stuff!";
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
exit();
}
$words = array();
while ($row = mysql_fetch_array($result)) {
$words[] = array(
'id' => $row['id'],
'letter' => $row['letter']);
}


include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
$sql2 = "SELECT id, name FROM fname";
$result = mysql_query($sql2, $linkst);
if (!$result ) {
$error = "Error retrieving names from database!";
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
  exit();
  }
  $users = array();
while ($row = mysql_fetch_array($result)) {
$users[] = array(
'id' => $row['id'],
'name' => $row['name']
);
}
include 'storm.html.php';

/*
scp storm.csv andrewsykes@northwolds.serveftp.net:/Users/andrewsykes
 LOAD DATA LOCAL INFILE '../../../../users/andrewsykes/storm.csv' INTO TABLE fname FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' LINES TERMINATED BY '\r';
 
 SELECT * from fchar  INTO OUTFILE '../../../../users/andrewsykes/sunday.csv' FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' LINES TERMINATED BY '/n';
 */
?>