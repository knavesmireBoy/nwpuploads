<?php
require_once __DIR__ . '/config.php';
include_once HELPERS;
include_once ACCESS;

var_dump(HELPERS);
//require_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/api/includes/access.inc.php';

function fromPayload($str, ...$args)
{
    return implode(' ', array_merge([$str], $args));
}

$nwpuploaded = function ($arg) {
    return $_FILES['upload'][$arg];
};

function getUploadedFile()
{
    $uploaded = function ($arg) {
        return $_FILES['upload'][$arg];
    };
    $uploadfile = $uploaded('tmp_name');
    $realname = $uploaded('name');
    $ext = preg_replace('/(.*)(\.[^0-9.]+$)/i', '$2', $realname);
    $time = time();
    //$uploadname = $time . getRemoteAddr() . $ext;
    $uploadname = $time . $ext;
    $filedname =  FILESTORE . $uploadname;
    return [$uploadfile, $uploadname, $filedname, $realname];
}

function clientFromUpload($txt, ...$args)
{
    $str = fromPayload($txt, ...$args);
    $tmptable = "(SELECT upload.userid FROM usr INNER JOIN upload ON upload.userid = usr.id WHERE upload.id=:id) AS tmp";
    $derived = " usr INNER JOIN client ON client.id = usr.client_id INNER JOIN upload ON usr.id = upload.userid INNER JOIN (SELECT client.id FROM client INNER JOIN usr on usr.client_id = client.id INNER JOIN $tmptable WHERE usr.id = tmp.userid) AS T ON client.id = T.id WHERE client.id = T.id";
    return $str . $derived;
}

function userFromUpload()
{
    return "SELECT usr.id, usr.name, usr.email, usr.client_id FROM upload INNER JOIN usr ON upload.userid = usr.id INNER JOIN (SELECT userid FROM upload WHERE id=:id) AS owt ON usr.id = owt.userid WHERE usr.id = owt.userid";
    return "SELECT usr.id, usr.name, usr.email, usr.client_id FROM upload INNER JOIN usr ON upload.userid = usr.id WHERE usr.id =:id";
}

function selectUploaded($order, $start, $limit)
{
    $select = "SELECT upload.id, filename, mimetype, description, filepath, file, size, time,  MID(file, 11, 14) AS origin, usr.email, usr.name";
    $from = " FROM upload INNER JOIN usr ON upload.userid=usr.id";
    $order = " ORDER BY $order LIMIT $limit OFFSET $start";
    return [$select, $from, $order];
}

function presentList($role, $flag = 'admin')
{
    $users = [];
    $client = [];
    if (isApproved($role, $flag)) {
        include CONNECT;
        $st = doQuery($pdo, "SELECT usr.id, usr.name FROM usr LEFT JOIN client ON usr.client_id=client.id WHERE client.domain IS NULL ORDER BY name", "Error retrieving details");
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $users[$row['id']] = $row['name'];
        }
        /*
    $sqlc = "SELECT employer.user_id, employer.name, employer.domain FROM
    (SELECT usr.name, usr.id as user_id, client.domain FROM usr INNER JOIN client ON RIGHT(usr.email, LENGTH(usr.email) - LOCATE('@', usr.email))=client.domain) AS employer";
    */
        $st = doQuery($pdo, "SELECT id, name, domain, tel FROM client ORDER BY name", "Database error fetching clients");
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        $st = $pdo->prepare("SELECT usr.id, usr.name FROM client INNER JOIN usr ON usr.client_id=client.id WHERE client.id=:id");
        foreach ($rows as $row) {
            $st->bindValue(":id", $row['id']);
            doPreparedQuery($st, "Database error fetching user");
            if ($st->fetch(PDO::FETCH_ASSOC)) {
                $client[$row['domain']] = $row['name'];
            }
        }
        return [$users, $client];
    }
}

function buildQuery($role, $flag = 'admin')
{
    return function ($select, $from, $order) use ($role, $flag) {
        $domainstr = fromStrPos(DBSYSTEM);
        include CONNECT;
        if (isApproved($role, $flag)) {
            //by default listing by user will list by first name "Amanda White, Sally Bowles"
            //where as lastname may be more desirable, so lets do that if you hit the file heading
            if (isset($_GET['sort']) && preg_match("/uf/", $_GET['sort'])) {
                $coalesce = orderByLastName();
                $select .= $coalesce;
            } else {
                $select .= ", usr.name as user";
            }
            $from .= " INNER JOIN userrole ON usr.id=userrole.userid";
            $where  = ' WHERE TRUE';
            $ext = isset($_GET['ext']) ? $_GET['ext'] : null;
            $getuser = isset($_GET['u']) ? $_GET['u'] : '';
            $bytext = isset($_GET['t']) ? $_GET['t'] : '';
            $byuser = isset($byuser) ? $byuser : $getuser;
            if ($ext) {
                if ($ext == 'owt') {
                    $where .= " AND (upload.filename NOT LIKE '%pdf' AND upload.filename NOT LIKE '%zip')";
                } else $where .= " AND upload.filename LIKE '%$ext'";
            }
            //CLIENTS USE EMAIL DOMAIN AS ID IN DROP DOWN THERFORE NOT A NUMBER
            //check $byuser is not zero
            if (!empty($byuser) && is_numeric($byuser)) {
                if ($byuser == $getuser) {
                    $where .= " AND usr.id=$byuser";
                }
            } else {
                if ($getuser) {
                    $where .= " AND $domainstr='$getuser'";
                }
            }
            if ($bytext) {
                $where .= " AND upload.filename LIKE '%$bytext%'";
            }
        } else {
            $email = $_SESSION['email'];
            $st = $pdo->prepare("SELECT usr.id, usr.name FROM usr WHERE usr.client_id IS NULL AND usr.email=:email");
            $st->bindValue(":email", "$email");
            doPreparedQuery($st, 'Error retreiving user details');
            $row = $st->fetch(PDO::FETCH_ASSOC);
            $where = $row ? " WHERE usr.email='$email'" : " WHERE client.domain = $domainstr";
            $i = strpos($email, '@');
            $dom = substr($email, $i + 1);
            if (!$row) {
                $where .= " AND client.domain = '$dom'";
            }
        }
        $sql = $select;
        $tel = ", client.name AS client, client.tel";
        //note LEFT join to include just 'users' also
        $from .= " LEFT JOIN client ON usr.client_id = client.id";
        $sql .= $tel . $from . $where . $order;
        return [$pdo, $sql];
    };
}

function myDomain($fileid)
{
    include CONNECT;
    $sql = userFromUpload();
    $st = $pdo->prepare($sql);
    $st->bindValue(":id", $fileid);
    doPreparedQuery($st, 'Failed to obtain userid');
    $rows = $st->fetchAll(PDO::FETCH_NUM);
    $multi = count($rows) > 1;
    $st = $pdo->prepare($sql);
    $st->bindValue(":id", $fileid);
    doPreparedQuery($st, 'Failed to obtain userid');
    list($ownerid, $ownername, $email) = $st->fetch(PDO::FETCH_NUM);
    $editor = $email === $_SESSION['email'];

    $sql = clientFromUpload("SELECT ", "upload.userid,", "usr.name,", "client.domain FROM ");
    $st = $pdo->prepare($sql);
    $st->bindValue(":id", $fileid);
    doPreparedQuery($st, 'Failed to obtain userid');
    $rows = $st->fetchAll(PDO::FETCH_NUM);
    $multi = $multi || count($rows) > 1;
    $sql .= " LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->bindValue(":id", $fileid);
    doPreparedQuery($st, 'Failed to obtain userid');
    list($userid, $name, $domain) = $st->fetch(PDO::FETCH_NUM);
    return [$ownerid, $ownername, $domain, $multi, $editor];
}
//setExtent do this here
setExtent(0);
$predicates = [partial('preg_match', '/^nwp/')];
$pageid = 'upload';
$pagetitle = 'Log In';
$pagehead = 'Log In!';
$error = '';
$user_id = 0;
$text = '';
$suffix = '';
$lib = ['nofile' => "<h4>'There was no file uploaded!'</h4>", 'fetch_files' => '<h4>Database error fetching stored files.</h4>', 'delete_file' => '<h4>Error deleting file.</h4>', 'file_list' => '<h4>Database error requesting the list of files.</h4>'];
$clientlist = null;
$display = 5;
$tel = '';
$call = '';
$disabled  = '';
$getuser = '';
$bytext = '';
$byuser = '';
$ext = null;

if (!userIsLoggedIn()) {
    include TEMPLATE . 'login.html.php';
    exit();
}
