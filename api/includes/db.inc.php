<?php
try {

    $env = getenv();
    preg_match('/[^:]+:\/\/[^:]+:([^@]+)@(.+)/', $env['DATABASE_URL'] ?? '', $matches);
    $pwd = $matches[1] ?? null;
    $connect = $matches[2] ?? null;

    if (!$pwd) {
        throw new Exception('Unable to connect to the database server');
    }
    $params = ['host' => '127.0.0.1', 'port' => 5432, 'database' => 'uploads', 'user' => 'andrewjsykes', 'password' => 'covid19krauq'];
    $params = ['host' => $connect, 'port' => 5432, 'database' => 'uploads', 'user' => 'neondb_owner', 'password' => $pwd];
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
