<?php
$link = mysqli_connect('localhost', 'root', 'krauq');
if (!$link)
{
$error = 'Unable to connect to the database server.'. mysqli_error();
include 'error.html.php';
exit();
}

if (!mysqli_select_db($link, 'uploads'))
{
$error = 'Unable to locate the uploads database.' . mysqli_error();
include 'error.html.php';
exit();
}
/*
$linkst = mysql_connect('localhost', 'root', 'krauq');
if (!$linkst )
{
$error = 'Unable to connect to the database server.'. mysql_error();
include '../error.html.php';
exit();
}
if (!mysql_select_db('storm', $linkst ))
{
$error = 'Unable to locate the storm database.' . mysql_error();
include '../error.html.php';
exit();
}*/
?>