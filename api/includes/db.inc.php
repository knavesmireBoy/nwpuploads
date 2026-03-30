<?php
try {
    $env = getenv();
    $db = $env['DATABASE_URL'];
    preg_match('[^:]+:([^@])+@(.)+/', $db, $matches);
    dump($matches);
    $params = ['host' => '127.0.0.1', 'port' => 5432, 'database' => 'uploads', 'user' => 'andrewjsykes', 'password' => 'covid19krauq'];
    $params = ['host' => 'ep-rough-term-abqfwn54-pooler.eu-west-2.aws.neon.tech', 'port' => 5432, 'database' => 'uploads', 'user' => 'neondb_owner', 'password' => 'npg_njmU0gaYvNV4'];
    $db = sprintf(
        "pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
        $params['host'],
        $params['port'],
        $params['database'],
        $params['user'],
        $params['password']
    );
    $pdo = new PDO($db);
  //  $pdo = new PDO('mysql:host=localhost;dbname=uploads', 'root', 'covid19krauq');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('SET search_path TO uploads');

   // $pdo->exec("SET NAMES utf8");
    //$conn = mysql_connect('localhost', 'root', 'krauq');
} catch (PDOException $e) {
    $output = 'Unable to connect to the database server: ' . $e->getMessage();
    $error = $output;
    include TEMPLATE . 'output.html.php';
    exit();
}
