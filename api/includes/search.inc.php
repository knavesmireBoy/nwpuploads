<?php
include CONNECT;
$tel = '';
$from .= " INNER JOIN userrole ON usr.id=userrole.userid";
$user_id =  $_GET['user'] ?? ''; //either a user id (int) or a client domain (str)
//$select .= ", usr.name as user";
$check = NULL;
$domainstr = fromStrPos(DBSYSTEM);
if ($priv == 'Admin') {
    //will either return empty set(no error) or produce count. Test to see if a client has been selected.
    $sql = "SELECT domain FROM client WHERE domain=:id";
    $st = $pdo->prepare($sql);
    $st->bindValue(":id", $user_id);
    doPreparedQuery($st, "Unable to find domain");
    $row = $st->fetch(PDO::FETCH_ASSOC);
    //user_id is text(domain) for Clients
    if ($row) {
        $dom = $row['domain'];
        $from .= " INNER JOIN client ON $domainstr = client.domain ";
        $where = " WHERE domain='$dom'";
        $check = count($row);
    } else {
        if ($user_id != '') {
            $where = " WHERE usr.id=$user_id";
        } else {
            $where = ' WHERE TRUE';
        }
    }
} //admin
else { //multi client
    if ($user_id != '') { // A user is selected 
        $where = " WHERE usr.id=$user_id";
    } else {
        $email = $_SESSION['email'];
        $where = " WHERE usr.email='$email'";
    }
}
$text = $_GET['text'];
if ($text != '') { // Some search text was specified 
    $where .= " AND upload.filename LIKE '%$text%'";
}
$suffix = $_GET['suffix'];
if (isset($suffix)) {
    if ($suffix == 'owt') {
        $where .= " AND (upload.filename NOT LIKE '%pdf' AND upload.filename NOT LIKE '%zip')";
    } elseif ($suffix == 'pdf' || $suffix == 'zip') {
        $where .= " AND upload.filename LIKE '%$suffix'";
        //$where .= sprintf(" AND upload.filename LIKE %s", GetSQLValueString('%'.$suffix, "text"));//Tricky percent symbol
    }
}
$sql =  $select . $from . $where . $order;
$st = doQuery($pdo, $sql, '<p>Error fetching file details!</p>');
$res = $st->fetch();
//$where .= " GROUP BY upload.id ";
$sqlcount = $select . ', COUNT(upload.id) as total ' . $from . $where . $order;
$sqlcount = $select . $from . $where . $order;
dump($sqlcount);
$st =  doQuery($pdo, $sqlcount, '<p>Error getting file count, innit</p>');
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
$records = empty($rows) ? 0 : $rows[0]['total'];
if ($records > $display) {
    $pages = ceil($records / $display);
} else {
    $pages = 1;
}


$files = array();
foreach ($rows as $row) {
    $files[] = array(
        'id' => $row['id'],
        'user' => $row['user'],
        'email' => $row['email'],
        'filename' => $row['filename'],
        'mimetype' => $row['mimetype'],
        'description' => $row['description'],
        'filepath' => $row['filepath'],
        'file' => $row['file'],
        'origin' => $row['origin'],
        'time' => $row['time'],
        'size' => $row['size'],
        'tel' => $row['tel'] ?? ''
    );
}
