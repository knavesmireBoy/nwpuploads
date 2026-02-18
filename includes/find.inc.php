<?php
$template = '/search.html.php';
//CUSTOMISES SELECT MENU overwriting DEFAULT $client and $users
if ($priv != "Admin") {
    $email = $_SESSION['email'];
    $iskey = false;
    include CONNECT;
    $st = $pdo->prepare("SELECT $domainstr FROM user WHERE user.email=:email");
    $st->bindValue(":email", $email);
    doPreparedQuery($st, "Error finding domain");
    $row = $st->fetch(PDO::FETCH_NUM);
    $dom = $row[0];

    $sql = "SELECT COUNT(*) AS dom FROM user INNER JOIN client ON $domainstr=client.domain WHERE $domainstr=:dom AND client.domain=:dommo";

    $st = $pdo->prepare($sql);
    $st->bindValue(":dom", $dom);
    $st->bindValue(":dommo", $dom);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $count = $row ? $row['dom'] : [];

    if (count($count) > 0) {
        $where = " WHERE user.email=:email"; //client
    } else {
        $where = " WHERE user.id=:key"; //user
        $iskey = true;
    }
    $sql = "SELECT employer.id, employer.name FROM user INNER JOIN (SELECT user.id, user.name, client.domain FROM user INNER JOIN client ON $domainstr=client.domain) AS employer ON $domainstr=employer.domain $where";
    $st = $pdo->prepare($sql);
    if ($iskey) {
        $st->bindValue(":key", $key);
    } else {
        $st->bindValue(":email", $email);
    }
    doPreparedQuery($st, '<p>Database error fetching clients.</p>');
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) > 1) {
        $users = []; //resets user array to display users of current client
        foreach ($rows as $row) {
            $users[$row['id']] = $row['name'];
        }
    }
    //SELECT MENU in SEARCH for only more than one "employee"
    else {
        $users = [];
        $zero = true;
    }
    $client = [];
}
