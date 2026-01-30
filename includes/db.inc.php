<?php


try {
    $pdo = new PDO('mysql:host=localhost;dbname=uploads', 'root', 'covid19krauq');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('SET NAMES "utf8"');
    //$conn = mysql_connect('localhost', 'root', 'krauq');
} catch (PDOException $e) {
    $output = 'Unable to connect to the database server: ' . $e->getMessage();
    include '../templates/output.html.php';
    exit();
}

/*
$link = mysqli_connect('localhost', 'root', 'covidkrauq');

if (!$link)
{
$error = 'Unable to connect to the database server.'. mysqli_error($e);
include 'error.html.php';
exit();
}

if (!mysqli_select_db($link, 'uploads'))
{
$error = 'Unable to locate the uploads database.' . mysqli_error($e);
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