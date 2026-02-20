<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/config.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/helpers.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/access.inc.php';

function fromPayload($str, ...$args)
{
    return implode(' ', array_merge([$str], $args));
}

function clientFromUpload($txt, ...$args)
{
    $str = fromPayload($txt, ...$args);
    $tmptable = "(SELECT upload.userid FROM user INNER JOIN upload ON upload.userid = user.id WHERE upload.id=:id) AS tmp";
    $derived = " user INNER JOIN client ON client.id = user.client_id INNER JOIN upload ON user.id = upload.userid INNER JOIN (SELECT client.id FROM client INNER JOIN user on user.client_id = client.id INNER JOIN $tmptable WHERE user.id = tmp.userid) AS T ON client.id = T.id WHERE client.id = T.id";
    return $str . $derived;
}

function userFromUpload()
{
    return "SELECT user.id, user.name, user.email, user.client_id FROM upload INNER JOIN user ON upload.userid = user.id INNER JOIN (SELECT userid FROM upload WHERE id=:id) AS owt ON user.id = owt.userid WHERE user.id = owt.userid";
}

function selectUploaded($order, $start, $limit)
{
    $select = "SELECT upload.id, filename, mimetype, description, filepath, file, size, time,  MID(file, 11, 14) AS origin, user.email, user.name";
    $from = " FROM upload INNER JOIN user ON upload.userid=user.id";
    $order = " ORDER BY $order LIMIT $start, $limit";
    return [$select, $from, $order];
}


function presentList($role, $flag = 'admin')
{
    $users = [];
    $client = [];
    if (isApproved($role, $flag)) {
        include CONNECT;
        $sqlu = "SELECT user.id, user.name FROM user LEFT JOIN client ON user.client_id=client.id WHERE client.domain IS NULL ORDER BY name";

        $st = doQuery($pdo, $sqlu, "Error retrieving details");
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $users[$row['id']] = $row['name'];
        }
        /*
    $sqlc = "SELECT employer.user_id, employer.name, employer.domain FROM
    (SELECT user.name, user.id as user_id, client.domain FROM user INNER JOIN client ON RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))=client.domain) AS employer";
    */
        $sqlc = "SELECT name, domain, tel FROM client ORDER BY name";
        $st = doQuery($pdo, $sqlc, "Database error fetching clients");
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $client[$row['domain']] = $row['name'];
        }

        return [$users, $client];
    }
}

function buildQuery($role, $flag = 'admin')
{
    return function ($select, $from, $order) use ($role, $flag) {
        $domainstr = "RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))";
        include CONNECT;
        if (isApproved($role, $flag)) {
            //by default listing by user will list by first name "Amanda White, Sally Bowles"
            //where as lastname may be more desirable, so lets do that if you hit the file heading
            if (isset($_GET['sort']) && preg_match("/uf/", $_GET['sort'])) {
                $select .= ", COALESCE(NULLIF(SUBSTR(user.name, LENGTH(user.name) - LOCATE(' ', REVERSE(user.name)) +1), ''), user.name) AS `user`";
            } else {
                $select .= ", user.name as user";
            }
            $from .= " INNER JOIN userrole ON user.id=userrole.userid";
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
            if (isset($byuser) && is_numeric($byuser)) {
                if ($byuser = $getuser) {
                    $where .= " AND user.id=$byuser";
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
            $st = $pdo->prepare("SELECT user.id, user.name FROM user WHERE user.client_id IS NULL AND user.email=:email");
            $st->bindValue(":email", "$email");
            doPreparedQuery($st, 'Error retreiving user details');
            $row = $st->fetch(PDO::FETCH_ASSOC);
            $where = $row ? " WHERE user.email='$email'" : " WHERE client.domain = $domainstr";
            $i = strpos($email, '@');
            $dom = substr($email, $i + 1);
            if (!$row) {
                $where .= " AND client.domain = '$dom'";
            }
        }
        $sql = $select;
        $tel = ", client.name AS client, client.tel";
        //note LEFT join to include just 'users' also
        $from .= " LEFT JOIN client ON user.client_id = client.id";
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

    $sql = clientFromUpload("SELECT ", "upload.userid,", "user.name,", "client.domain FROM ");
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

$pagetitle = 'Log In';
$pagehead = 'Log In!';
$error = '';
$myip = '86.160.57.166';
$user_id = 0;
$text = '';
$suffix = '';
$lib = ['nofile' => "<h4>'There was no file uploaded!'</h4>", 'fetch_files' => '<h4>Database error fetching stored files.</h4>', 'delete_file' => '<h4>Error deleting file.</h4>', 'file_list' => '<h4>Database error requesting the list of files.</h4>'];
$clientlist = null;
$display = 5;
$tel = '';
$call = '';
$goto = __DIR__;
$disabled  = '';
$ext = null;
$getuser = '';
$bytext = '';
$byuser = '';

$uploaded = function ($arg) {
    return $_FILES['upload'][$arg];
};

if (!userIsLoggedIn()) {
    include TEMPLATE . 'login.html.php';
    exit();
}
//public page
if ($roleplay = userHasWhatRole()) {
    list($key, $priv) = $roleplay;
    //!!?!! V. USEFUL VARIABLE IN GLOBAL SPACE
    $domainstr = "RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))";
} else {
    $error = 'Only valid clients may access this page.';
    include TEMPLATE . 'accessdenied.html.php';
    exit(); // endof OBTAIN access level
}

if ($priv === 'Browser') {
    $disabled = 'disabled';
}
$template = '/upload.html.php';
$pagetitle = 'File Uploads';

if (isset($_POST['action']) && $_POST['action'] == 'upload') {
    //Bail out if the file isn't really an upload
    if (!is_uploaded_file($_FILES['upload']['tmp_name'])) {
        header("Location: ./?nofile");
        exit();
    }
    $uploadfile = $uploaded('tmp_name');
    $realname = $uploaded('name');
    $ext = preg_replace('/(.*)(\.[^0-9.]+$)/i', '$2', $realname);
    $time = time();
    //$uploadname = $time . getRemoteAddr() . $ext;
    $uploadname = $time . $ext;
    $path = '../../filestore/';
    $filedname =  $path . $uploadname;
    // Copy the file (if it is deemed safe)
    if (!copy($uploadfile, $filedname)) {
        $error = "Could not save file as $filedname!";
        include TEMPLATE . 'error.html.php';
        exit();
    }
    if (!empty($_POST['user'])) { //ie Admin selects user
        $key = $_POST['user'];
        include CONNECT;
        $st = $pdo->prepare("SELECT domain FROM client WHERE domain=:id");
        $st->bindValue(":id", $key);
        doPreparedQuery($st, 'Error fetching domain');
        $row = $st->fetch(PDO::FETCH_NUM);
        if ($row && count($row) > 0) {
            //RETURNS one user, as relationship between file and user is one to one.
            $sql = "SELECT employer.user_name, employer.user_id FROM (SELECT user.name AS user_name, user.id AS user_id, client.domain, client.id FROM user INNER JOIN client ON $domainstr = client.domain INNER JOIN userrole ON user.id = userrole.userid WHERE userrole.roleid LIKE :myrole ORDER BY client.id) AS employer WHERE employer.domain=:id LIMIT 1";
            $st = $pdo->prepare($sql);
            $st->bindValue(":id", $key);
            $st->bindValue(":myrole", 'Client%');
            doPreparedQuery($st, 'Error fetching user details');
            $row = $st->fetch(PDO::FETCH_ASSOC);
            $key = $row ? $row['user_id'] : null;
            if (!$key) {
                //$key will be empty if above query returned empty set, reset
                $key = $_POST['user'];
                $sql = "SELECT user.id from user INNER JOIN client ON user.client_id=client.id WHERE user.email=:id";
                $st = $pdo->prepare($sql);
                $st->bindValue(":id", $key);
                doPreparedQuery($st, 'Error fetching user details');
                $row = $st->fetch(PDO::FETCH_ASSOC);
                $key = $row ? $row['id'] : 0;
            } // @ clients use domain or full email as key if neither tests produce a result key refers to a user only
        } //END OF COUNT
    } //Admin uploading for user

    // Prepare user-submitted values for safe database insert
    include CONNECT;
    $uploaddesc = $_POST['desc'] ?? '';
    $size =  $uploaded('size') / 1024;

    $sql = "INSERT INTO upload (filename, mimetype, description, filepath, file, size, userid, time) VALUES(:realname, :uploadtype,:uploaddesc,:pth,:uploadname,:sized,:userid, NOW())";

    $st = $pdo->prepare($sql);
    $st->bindValue(":realname", $realname);
    $st->bindValue(":uploadtype", $uploaded('type'));
    $st->bindValue(":uploaddesc", $uploaddesc);
    $st->bindValue(":pth", $path);
    $st->bindValue(":uploadname", $uploadname);
    $st->bindValue(":sized", $size);
    $st->bindValue(":userid", $key);
    $res = doPreparedQuery($st, "<p>Database error storing file information!</p>");
    $insertId = lastInsert($pdo);
    $sql = "SELECT user.email, user.name, upload.id, upload.filename FROM user INNER JOIN upload ON user.id=upload.userid WHERE upload.id=:id";
    $st = $pdo->prepare($sql);
    $st->bindValue(":id", $insertId);
    doPreparedQuery($st, 'Error selecting email address.');

    $row = $st->fetch(PDO::FETCH_ASSOC);
    $email = $row['email'];
    $file = $row['filename'];
    $name = $row['name'];
    if ($priv == 'Admin') {
        $body =  'We have just uploaded the file' . $file . 'for checking.';
        $body = wordwrap($body, 70);
        //mail($email, $file, $body, "From: $name <{$_SESSION['email']}>");
    }
    header('Location: .');
    exit();
} // end of upload_____________________________________________________________________

if (isset($_GET['action']) and isset($_GET['id'])) {
    include CONNECT;
    $sql = "SELECT filename, mimetype, filepath, file, size FROM upload WHERE id =:id";
    $st = $pdo->prepare($sql);
    $st->bindValue(":id", $_GET['id']);
    doPreparedQuery($st, '<p>Database error fetching requested file.</p>');
    $file = $st->fetch(PDO::FETCH_ASSOC);

    if (!$file) {
        $error = 'File with specified ID not found in the database!';
        include TEMPLATE . 'error.html.php';
        exit();
    }
    $filename = $file['filename'];
    $mimetype = $file['mimetype'];
    $filepath = $file['filepath'];
    $uploadfile = $file['file'];
    $size = $file['size'];
    $filepath .= $uploadfile;
    $fullpath = $_SERVER['DOCUMENT_ROOT'] . $filepath;
    if (!file_exists($filepath)) {
        header("Location: .");
        exit();
    }
    $filedata = file_get_contents($fullpath);
    $disposition = $_GET['action'] == 'download' ? 'attachment' : 'inline';
    //$mimetype = 'application/x-unknown'; application/octet-stream
    //Content-type must come before Content-disposition
    header("Content-type: $mimetype");
    //this works..
    header('Content-disposition: ' . $disposition . '; filename=' . '"' . $filename . '"');
    //header("Content-Transfer-Encoding: binary");
    header('Content-length:' . strlen($filedata));
    echo $filedata;
    exit();
} // end of download

if (isset($_POST['action']) && $_POST['action'] == 'delete') {
    //obtain user id/client name
    $id = $_POST['id']; //id of file
    $title = "Prompt";
    $prompt = "Are you sure you want to delete this file?";
    $call = "confirm";
    $pos = "Yes";
    $neg = "No";
    $action = '';
    list($ownerid, $ownername, $domain, $multi, $editor) = myDomain($id);
    $template = '/prompt.html.php';
}

if (isset($_POST['confirm']) && $_POST['confirm'] == 'Yes') {
    $id = $_POST['id'];
    $prompt = "Select the extent of deletions";
    $del = "proceed";
    $ownerid = $_POST['ownerid'];
    $ownername = $_POST['ownername'];
    $domain = $_POST['domain'];
    $multi = $_POST['multi'];
    $editor = $_POST['editor'];
    $template = '/prompt.html.php';
}

if (isset($_POST['proceed']) && $_POST['proceed'] === 'destroy') {
    include CONNECT;
    $path = '../../filestore/';
    $_extent = $_POST['extent'];
    $deletejoins = array(
        /*doozy, obtain client id from file id to filter list of client files */
        "DELETE upload FROM user INNER JOIN client ON client.id = user.client_id INNER JOIN upload  ON user.id = upload.userid INNER JOIN (SELECT client.id FROM client INNER JOIN user on user.client_id = client.id  INNER JOIN (SELECT upload.userid FROM user INNER JOIN upload ON upload.userid = user.id WHERE upload.id=:id) AS tmp WHERE user.id = tmp.userid) AS T ON client.id = T.id WHERE client.id = T.id",
        "DELETE upload FROM upload INNER JOIN user ON upload.userid = user.id INNER JOIN (SELECT userid FROM upload  WHERE id =:id) AS owt ON user.id = owt.userid WHERE user.id = owt.userid",
        "DELETE FROM upload WHERE id =:id   "
    );

    $selectors = [
        clientFromUpload("SELECT upload.file FROM "),
        "SELECT upload.file FROM upload INNER JOIN user ON upload.userid = user.id INNER JOIN (SELECT userid FROM upload  WHERE id=:id) AS owt ON user.id = owt.userid WHERE user.id = owt.userid",
        "SELECT upload.file FROM upload WHERE id=:id"
    ];

    $lib = ['c' => $selectors[0], 'u' => $selectors[1], 'f' => $selectors[2]];
    if (isset($lib[$_extent])) {
        $sql = $lib[$_extent];
    } else {
        header('Location: .');
        exit();
    }
    $st = $pdo->prepare($sql);
    $st->bindValue(":id", $_POST['id']);
    doPreparedQuery($st, 'Error failed to delete file');
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        header("Location: ./?fetch_files");
        exit();
    }

    $sql = "DELETE FROM upload WHERE file=:f";
    $st = $pdo->prepare($sql);
    $location =  "Location: .";

    foreach ($rows as $row) {
        $file = $row['file'];
        $st->bindValue(":f", $file);
        $res = doPreparedQuery($st, 'Error deleting file.');
        if (!$res) { //delete file ref
            $location =  "Location: ./?delete_file";
            break;
        }
        $thepath = $path . $file;
        if (file_exists($thepath)) {
            unlink($thepath);
        }
    }
    header($location);
    exit();
} //________________________end of confirm/delete

if (isset($_POST['confirm']) && $_POST['confirm'] === 'No') { //swap
    $id = $_POST['id'];
    $ownerid = $_POST['ownerid'];
    $ownername = $_POST['ownername'];
    $domain = $_POST['domain'];
    $multi = $_POST['multi'];
    $pos = "Yes";
    $neg = "No";
    $action = '';

    if ($multi) {
        $call = "affirm";
        $prompt = "Change ownership on ALL files?";
        $template = '/prompt.html.php';
    } else {
        $call = "swap";
    }
}

//SWITCH OWNER OF FILE OR JUST UPDATE DESCRIPTION (FILE AMEND BLOCK)
if ($call === 'swap' || isset($_POST['swap']) || isset($_POST['affirm'])) {
    include CONNECT;
    $template = '/update.html.php';
    $answer = $answer ?? $_POST['swap'] ?? NULL;
    $email = $_SESSION['email'];

    $sql = "SELECT upload.id, filename, description, upload.userid, user.name FROM upload INNER JOIN user ON upload.userid=user.id  WHERE upload.id=:id";
    $st = $pdo->prepare($sql);
    $st->bindValue(":id", $_POST['id']);
    doPreparedQuery($st, 'Database error fetching stored files.');
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $filename = $row['filename'];
    $description = $row['description'];
    $userid = $row['userid'];
    $aname = $row['name'];
    $button = "Update";
    $action = '';
    $rows = [];
    $id =  $_POST['id']; //CRUCIAL to pass id to file amend form (update.html.php)

    if (preg_match("/client/i", $priv)) {
        $sql = "SELECT employer.id, employer.name FROM upload INNER JOIN user ON upload.userid = user.id INNER JOIN (SELECT user.id, user.name, client.domain FROM user INNER JOIN client ON $domainstr=client.domain) AS employer ON $domainstr=employer.domain WHERE upload.id=:id ORDER BY name"; //colleagues
        $st = $pdo->prepare($sql);
        $st->bindValue(":id", $row['id']);
        doPreparedQuery($st, 'Database error fetching colleagues.');
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $colleagues[$row['id']] = $row['name'];
        }
    }
    if ($priv === 'Admin') {
        $sql = "SELECT user.name, user.id FROM user LEFT JOIN client ON user.client_id=client.id  WHERE client.domain IS NULL UNION SELECT user.name, user.id FROM user INNER JOIN client ON user.client_id=client.id ORDER BY name";
        $st = doQuery($pdo, $sql, 'Database error fetching users.');
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $all_users[$row['id']] = $row['name'];
        }
    }
} ///

if (isset($_POST['original'])) {
    //CAN ONLY BE SET BY ADMIN, 'original' is common to both options of file amend block
    include CONNECT;
    $user = !empty($_POST['colleagues']) ? $_POST['colleagues'] : (!empty($_POST['user']) ? $_POST['user'] : $_POST['original']);
    $id = intval($_POST['fileid']);
    $filename = $_POST['filename'];
    if ($_POST['answer'] == 'Yes') {
        $st = $pdo->prepare("UPDATE upload SET userid=:userid WHERE userid=:orig");
        $st->bindValue(':userid', $user);
        $st->bindValue(':orig', $_POST['original']);
    } else {
        $st = $pdo->prepare("UPDATE upload SET userid=:userid, description=:descrip, filename=:fname WHERE id =:fileid");
        $st->bindValue(':userid', $user);
        $st->bindValue(':descrip', $_POST['description']);
        $st->bindValue(':fname', $filename);
        $st->bindValue(':fileid', $_POST['fileid']);
    }
    doPreparedQuery($st, '<p>Error Updating Details!</p>');
    header('Location: . ');
    exit();
}
///end of F I L E AMEND BLOCK___________________________________________________________________

//

if (isset($_GET['p']) && is_numeric($_GET['p'])) {
    $pages = $_GET['p'];
} else { // counts all files
    $pages = 1;
    include CONNECT;
    $sql = "SELECT COUNT(upload.id) as total from upload ";
    if (preg_match("/client/i", $priv)) {
        $sql .= " INNER JOIN user on upload.userid = user.id WHERE user.email=:email";
    }
    $st = $pdo->prepare($sql);
    $st->bindValue(":email", $_SESSION['email']);
    doPreparedQuery($st, "Database error requesting the list of files:");
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        header("Location: ./?file_list");
        exit();
    }
    $records = $row['total'];
    if ($records > $display) {
        $pages = ceil($records / $display);
    }
} //end of IF NOT PAGES SET


if (isset($_GET['s']) and is_numeric($_GET['s'])) {
    $start = $_GET['s'];
} else {
    $start = 0;
}

$sorter = array('f' => 'filename ASC', 'ff' => 'filename DESC', 'u' => 'user ASC', 'uu' => 'user DESC', 'uf' => 'user ASC, filename ASC', 'uuf' => 'user DESC, filename ASC',  'uff' => 'user ASC, filename DESC',  'uuff' => 'user DESC, filename DESC', 'ut' => 'user ASC, time ASC', 'utt' => 'user ASC, time DESC', 'uut' => 'user DESC, time ASC', 'uutt' => 'user DESC, time DESC', 't' => 'time ASC', 'tt' => 'time DESC');

$sort = (isset($_GET['sort']) ? $_GET['sort'] : '1');

foreach ($sorter as $ix => $u) {
    if ($ix == $sort) break;
}
switch ($sort) {
    case $ix:
        $order_by = $sorter[$ix];
        break;
    default:
        $order_by = 'time DESC';
        $sort = 'tt';
        break;
}

//D I S P L A Y_______________________________________________________________
///Present list of users for administrators
list($users, $client) = presentList($priv);
//!!comes AFTER $users, $client
///will amend $users and $clients for non admin
if (isset($_GET['find'])) {
    include INCLUDES . "find.inc.php";
}

list($select, $from, $order) = selectUploaded($order_by, $start, $display);
//!!comes AFTER $select etc..
if (isset($_GET['action']) && $_GET['action'] === 'search') {
    include INCLUDES . 'search.inc.php';
    include_once TEMPLATE . 'base.html.php';
    include TEMPLATE . 'files.html.php';
    exit();
}
$build = buildQuery($priv, 'ADMIN');
list($pdo, $sql) = $build($select, $from, $order);
$st = doQuery($pdo, $sql, 'Database error fetching files. ');
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
$files = array();
foreach ($rows as $row) {
    $files[] = array(
        'id' => $row['id'],
        'user' => $row['name'],
        'client' => $row['client'],
        'email' => $row['email'],
        'filename' => $row['filename'],
        'mimetype' => $row['mimetype'],
        'description' => $row['description'],
        'filepath' => $row['filepath'],
        'file' => $row['file'],
        'origin' => $row['origin'],
        'time' => $row['time'],
        'tel' => $row['tel'],
        'size' => $row['size']
    );
}
$pagetitle = 'North Wolds Printers | The File Uploads';

list($qs, $state) = qsort('sort=');
$ufn = qUserHead('u');
$tfn = qHead('t');
$ffn = qHead('f', 'u');
$tmp = $qs ? "&sort=" : "?sort=";
$qs = $qs ? "?$qs" : '';
$qs = $qs . $tmp;
$qs = preg_replace("/&&/", "&", $qs);
$fhead = $qs . $ffn($state);
$uhead = $qs . $ufn($state);
$thead = $qs . $tfn($state);

include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/base.html.php';
$error =  $lib[$_SERVER["QUERY_STRING"]] ?? '';
$arr = getDefinedVars();
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/files.html.php';
