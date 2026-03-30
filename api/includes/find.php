<?php
$template = '/search.html.php';
//CUSTOMISES SELECT MENU overwriting DEFAULT $client and $users
if ($priv != "Admin") {
    $email = $_SESSION['email'];
    $iskey = false;
    include CONNECT;
    $st = $pdo->prepare("SELECT $domainstr FROM usr WHERE usr.email=:email");
    $st->bindValue(":email", $email);
    doPreparedQuery($st, "Error finding domain");
    $row = $st->fetch(PDO::FETCH_NUM);
    $dom = $row[0];
    $sql = "SELECT COUNT(*) AS dom FROM usr INNER JOIN client ON $domainstr=client.domain WHERE $domainstr=:dom AND client.domain=:dommo";
    $st = $pdo->prepare($sql);
    $st->bindValue(":dom", $dom);
    $st->bindValue(":dommo", $dom);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $count = $row ? $row['dom'] : [];

    if (count($count) > 0) {
        $where = " WHERE usr.email=:email"; //client
    } else {
        $where = " WHERE usr.id=:key"; //user
        $iskey = true;
    }
    $sql = "SELECT employer.id, employer.name FROM usr INNER JOIN (SELECT usr.id, usr.name, client.domain FROM usr INNER JOIN client ON $domainstr=client.domain) AS employer ON $domainstr=employer.domain $where";
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
