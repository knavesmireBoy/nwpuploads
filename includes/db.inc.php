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
