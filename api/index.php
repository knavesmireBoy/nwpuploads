<?php
require_once __DIR__ . '/config.php';
include_once HELPERS;
include_once ACCESS;


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
    $filename =  FILESTORE . $uploadname;
    $filename =  "/tmp/$uploadname";
    return [$uploadfile, $uploadname, $filename, $realname];
}

function clientFromUpload($txt, ...$args)
{
    $str = fromPayload($txt, ...$args);
    $tmptable = "(SELECT upload.userid FROM usr INNER JOIN upload ON upload.userid = usr.id WHERE upload.id=:id) AS myuser";
    $derived = " usr INNER JOIN client ON client.id = usr.client_id INNER JOIN upload ON usr.id = upload.userid INNER JOIN (SELECT client.id FROM client INNER JOIN usr on usr.client_id = client.id INNER JOIN $tmptable ON usr.id = myuser.userid WHERE usr.id = myuser.userid) AS myclient ON client.id = myclient.id WHERE client.id = myclient.id";
    return $str . $derived;
}

function userFromUpload()
{
    return "SELECT usr.id, usr.name, usr.email, usr.client_id FROM upload INNER JOIN usr ON upload.userid = usr.id INNER JOIN (SELECT userid FROM upload WHERE id=:id) AS owt ON usr.id = owt.userid WHERE usr.id = owt.userid";
    return "SELECT usr.id, usr.name, usr.email, usr.client_id FROM upload INNER JOIN usr ON upload.userid = usr.id WHERE usr.id =:id";
}

function selectUploaded($order, $start, $limit)
{
    $select = "SELECT upload.id, filename, mimetype, description, filepath, file, size, time,  SUBSTRING(file, 11, 14) AS origin, usr.email, usr.name";
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
                $coalesce = orderByLastName(DBSYSTEM);
                $select .= $coalesce;
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
        if (isset($coalesce)) {
            "SELECT upload.id, filename, mimetype, description, filepath, file, size, time, SUBSTRING(file, 11, 14) AS origin, usr.email, usr.name, COALESCE(NULLIF(SUBSTRING(usr.name FROM POSITION(' ' IN usr.name) +1), ''), usr.name) AS user, client.name AS client, client.tel FROM upload INNER JOIN usr ON upload.userid=usr.id INNER JOIN userrole ON usr.id=userrole.userid LEFT JOIN client ON usr.client_id = client.id WHERE TRUE ORDER BY usr.name ASC, filename ASC LIMIT 5 OFFSET 0";
        }
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

$pageid = 'upload';
$pagetitle = 'Log In';
$pagehead = 'Log In!';

if (!userIsLoggedIn()) {
    include TEMPLATE . 'login.html.php';
    exit();
}

//public page
if ($roleplay = obtainUserRole()) {
    list($key, $priv) = $roleplay;
    //!!?!! V. USEFUL VARIABLE IN GLOBAL SPACE
    $nwpdomainstr = fromStrPos(DBSYSTEM);
    dump('nick');
} else {
    $error = 'Only valid clients may access this page.';
    include TEMPLATE . 'accessdenied.html.php';
    exit(); // endof OBTAIN access level
}


if ($priv === 'Browser') {
    $disabled = 'disabled';
}

//setExtent do this here
setExtent(0);
$predicates = [partial('preg_match', '/^nwp/')];
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
$pagetitle = 'File Uploads';

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

if (isset($_POST['action']) && $_POST['action'] == 'upload') {
    if (!is_uploaded_file($_FILES['upload']['tmp_name'])) {
        header("Location: ./?nofile");
        exit();
    }
    list($nwpuploadfile, $nwpuploadname, $nwpfilename, $nwprealname) = getUploadedFile();

    // Copy the file (if it is deemed safe)
    if (!copy($nwpuploadfile, $nwpfilename)) {
        $error = "Could not save file as $nwpfilename!";
        include TEMPLATE . 'error.html.php';
        exit();
    }
    if (!empty($_POST['user'])) { //ie Admin selects user
        $key = $_POST['user'];
        include CONNECT;
        $nwpst = $pdo->prepare("SELECT domain FROM client WHERE domain=:id");
        $nwpst->bindValue(":id", $key);
        doPreparedQuery($nwpst, 'Error fetching domain');
        $nwprow = $nwpst->fetch(PDO::FETCH_NUM);
        if ($nwprow && count($nwprow) > 0) {
            $nwpdomainstr = fromStrPos(DBSYSTEM);
            //RETURNS one user, as relationship between file and user is one to one.
            $nwpsql = "SELECT employer.user_name, employer.user_id FROM (SELECT usr.name AS user_name, usr.id AS user_id, client.domain, client.id FROM usr INNER JOIN client ON $nwpdomainstr = client.domain INNER JOIN userrole ON usr.id = userrole.userid WHERE userrole.roleid LIKE :myrole ORDER BY client.id) AS employer WHERE employer.domain=:id LIMIT 1";
            $nwpst = $pdo->prepare($nwpsql);
            $nwpst->bindValue(":id", $key);
            $nwpst->bindValue(":myrole", 'Client%');
            doPreparedQuery($nwpst, 'Error fetching user details');
            $nwprow = $nwpst->fetch(PDO::FETCH_ASSOC);
            $key = $nwprow ? $nwprow['user_id'] : null;
            if (!$key) {
                //$key will be empty if above query returned empty set, reset
                $key = $_POST['user'];
                $nwpsql = "SELECT usr.id from usr INNER JOIN client ON usr.client_id=client.id WHERE usr.email=:id";
                $nwpst = $pdo->prepare($nwpsql);
                $nwpst->bindValue(":id", $key);
                doPreparedQuery($nwpst, 'Error fetching user details');
                $nwprow = $nwpst->fetch(PDO::FETCH_ASSOC);
                $key = $nwprow ? $nwprow['id'] : 0;
            } // @ clients use domain or full email as key if neither tests produce a result key refers to a user only
        } //END OF COUNT
    } //Admin uploading for user

    // Prepare user-submitted values for safe database insert
    include CONNECT;

    $nwpsql = "INSERT INTO upload (filename, mimetype, description, filepath, file, size, userid, time) VALUES(:realname, :uploadtype,:uploaddesc,:pth,:uploadname,:sized,:userid, NOW())";

    $nwpst = $pdo->prepare($nwpsql);
    $nwpst->bindValue(":realname", $nwprealname);
    $nwpst->bindValue(":uploadtype", $nwpuploaded('type'));
    $nwpst->bindValue(":uploaddesc", $_POST['desc'] ?? '');
    $nwpst->bindValue(":pth", FILESTORE);
    $nwpst->bindValue(":uploadname", $nwpuploadname);
    $nwpst->bindValue(":sized", $nwpuploaded('size') / 1024);
    $nwpst->bindValue(":userid", $key);
    $res = doPreparedQuery($nwpst, "<p>Database error storing file information!</p>");
    $nwpInsertId = lastInsert($pdo, DBSYSTEM, 'upload');
    $nwpsql = "SELECT usr.email, usr.name, upload.id, upload.filename FROM usr INNER JOIN upload ON usr.id=upload.userid WHERE upload.id=:id";
    $nwpst = $pdo->prepare($nwpsql);
    $nwpst->bindValue(":id", $nwpInsertId);
    doPreparedQuery($nwpst, 'Error selecting email address.');

    $nwprow = $nwpst->fetch(PDO::FETCH_ASSOC);
    $nwpemail = $nwprow['email'];
    $nwpfile = $nwprow['filename'];
    $nwpname = $nwprow['name'];
    if ($priv == 'Admin') {
        $nwpbody =  'We have just uploaded the file' . $nwpfile . 'for checking.';
        $nwpbody = wordwrap($nwpbody, 70);
        //mail($nwpemail, $nwpfile, $body, "From: $name <{$_SESSION['email']}>");
    }
    header('Location: .');
    exit();
} // end of upload_____________________________________________________________________

if (isset($_GET['action']) && isset($_GET['id'])) {
    include CONNECT;
    $nwpsql = "SELECT filename, mimetype, filepath, file, size FROM upload WHERE id =:id";
    $nwpst = $pdo->prepare($nwpsql);
    $nwpst->bindValue(":id", $_GET['id']);
    doPreparedQuery($nwpst, '<p>Database error fetching requested file.</p>');
    $file = $nwpst->fetch(PDO::FETCH_ASSOC);

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
    if (!file_exists($filepath)) {
        header("Location: .");
        exit();
    }

    $filedata = file_get_contents($filepath);
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
    $delete = "proceed";
    $ownerid = $_POST['ownerid'];
    $ownername = $_POST['ownername'];
    $domain = $_POST['domain'];
    $multi = $_POST['multi'];
    $editor = $_POST['editor'];
    $template = '/prompt.html.php';
}

if (isset($_GET['upload'])) {
    $template = 'upload.html.php';
}

if (isset($_POST['proceed']) && $_POST['proceed'] === 'destroy') {
    include CONNECT;
    $path = FILESTORE;
    $_extent = $_POST['extent'];
    $deletejoins = array(
        /*doozy, obtain client id from file id to filter list of client files */
        "DELETE upload FROM usr INNER JOIN client ON client.id = usr.client_id INNER JOIN upload  ON usr.id = upload.userid INNER JOIN (SELECT client.id FROM client INNER JOIN usr on usr.client_id = client.id  INNER JOIN (SELECT upload.userid FROM usr INNER JOIN upload ON upload.userid = usr.id WHERE upload.id=:id) AS tmp WHERE usr.id = tmp.userid) AS T ON client.id = T.id WHERE client.id = T.id",
        "DELETE upload FROM upload INNER JOIN usr ON upload.userid = usr.id INNER JOIN (SELECT userid FROM upload  WHERE id =:id) AS owt ON usr.id = owt.userid WHERE usr.id = owt.userid",
        "DELETE FROM upload WHERE id =:id   "
    );

    $selectors = [
        clientFromUpload("SELECT upload.file FROM "),
        "SELECT upload.file FROM upload INNER JOIN usr ON upload.userid = usr.id INNER JOIN (SELECT userid FROM upload  WHERE id=:id) AS owt ON usr.id = owt.userid WHERE usr.id = owt.userid",
        "SELECT upload.file FROM upload WHERE id=:id"
    ];

    $lib = ['c' => $selectors[0], 'u' => $selectors[1], 'f' => $selectors[2]];
    if (isset($lib[$_extent])) {
        $nwpsql = $lib[$_extent];
    } else {
        header('Location: .');
        exit();
    }
    $nwpst = $pdo->prepare($nwpsql);
    $nwpst->bindValue(":id", $_POST['id']);
    doPreparedQuery($nwpst, 'Error failed to delete file');
    $rows = $nwpst->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        header("Location: ./?fetch_files");
        exit();
    }

    $nwpsql = "DELETE FROM upload WHERE file=:f";
    $nwpst = $pdo->prepare($nwpsql);
    $location =  "Location: .";

    foreach ($rows as $row) {
        $file = $row['file'];
        $nwpst->bindValue(":f", $file);
        $res = doPreparedQuery($nwpst, 'Error deleting file.');
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
    $prompt = 'Proceed to Update';
    $ownerid = $_POST['ownerid'];
    $ownername = $_POST['ownername'];
    $domain = $_POST['domain'];
    $multi = $_POST['multi'];
    $editor = $_POST['editor'];
    $pos = "Yes";
    $neg = "No";
    $action = '';
    $call = "update";
    $prompt = $multi ? "Change ownership on ALL files?" : $prompt;
    $template = '/prompt.html.php';
    $call = $multi ? 'swap' : $call;
}
//$call === 'swap' || isset($_POST['swap']) || 
//SWITCH OWNER OF FILE OR JUST UPDATE DESCRIPTION (FILE AMEND BLOCK)
if (isset($_POST['update']) || isset($_POST['swap'])) {
    include CONNECT;
    $swap = 'No';
    if (isset($_POST['update']) && $_POST['update'] === 'No') {
        header("Location: .");
        exit();
    }
    if (isset($_POST['swap'])) {
        $swap = $_POST['swap'];
    }
    $template = '/update.html.php';
    $answer = $answer ?? $_POST['affirm'] ?? NULL;
    $email = $_SESSION['email'];

    $nwpsql = "SELECT upload.id, filename, description, upload.userid, usr.name FROM upload INNER JOIN usr ON upload.userid=usr.id  WHERE upload.id=:id";
    $nwpst = $pdo->prepare($nwpsql);
    $nwpst->bindValue(":id", $_POST['id']);
    doPreparedQuery($nwpst, 'Database error fetching stored files.');
    $nwprow = $nwpst->fetch(PDO::FETCH_ASSOC);
    $filename = $nwprow['filename'];
    $description = $nwprow['description'];
    $userid = $nwprow['userid'];
    //$aname = $row['name'];
    $button = "Update";
    $action = '';
    $rows = [];
    $id =  $_POST['id']; //CRUCIAL to pass id to file amend form (update.html.php)

    if (preg_match("/client/i", $priv)) {
        $nwpdomainstr = fromStrPos(DBSYSTEM);
        $nwpsql = "SELECT employer.id, employer.name FROM upload INNER JOIN usr ON upload.userid = usr.id INNER JOIN (SELECT usr.id, usr.name, client.domain FROM usr INNER JOIN client ON $nwpdomainstr=client.domain) AS employer ON $nwpdomainstr=employer.domain WHERE upload.id=:id ORDER BY name"; //colleagues
        $nwpst = $pdo->prepare($nwpsql);
        $nwpst->bindValue(":id", $row['id']);
        doPreparedQuery($nwpst, 'Database error fetching colleagues.');
        $rows = $nwpst->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $colleagues[$row['id']] = $row['name'];
        }
    }
    if ($priv === 'Admin') {
        $nwpsql = "SELECT usr.name, usr.id FROM usr LEFT JOIN client ON usr.client_id=client.id  WHERE client.domain IS NULL UNION SELECT usr.name, usr.id FROM usr INNER JOIN client ON usr.client_id=client.id ORDER BY name";
        $nwpst = doQuery($pdo, $nwpsql, 'Database error fetching users.');
        $nwprows = $nwpst->fetchAll(PDO::FETCH_ASSOC);
        foreach ($nwprows as $nwprow) {
            $all_users[$nwprow['id']] = $nwprow['name'];
        }
    }
}

if (isset($_POST['original'])) {
    //CAN ONLY BE SET BY ADMIN, 'original' is common to both options of file amend block
    include CONNECT;
    $nwpuser = !empty($_POST['colleagues']) ? $_POST['colleagues'] : (!empty($_POST['user']) ? $_POST['user'] : $_POST['original']);
    $id = intval($_POST['fileid']);
    $nwpfilename = $_POST['filename'];
    if ($_POST['answer'] == 'Yes') {
        $nwpst = $pdo->prepare("UPDATE upload SET userid=:userid WHERE userid=:orig");
        $nwpst->bindValue(':userid', $nwpuser);
        $nwpst->bindValue(':orig', $_POST['original']);
    } else {
        $nwpst = $pdo->prepare("UPDATE upload SET userid=:userid, description=:descrip, filename=:fname WHERE id =:fileid");
        $nwpst->bindValue(':userid', $nwpuser);
        $nwpst->bindValue(':descrip', html($_POST['description']));
        $nwpst->bindValue(':fname', $nwpfilename);
        $nwpst->bindValue(':fileid', $_POST['fileid']);
    }
    doPreparedQuery($nwpst, '<p>Error Updating Details!</p>');
    header('Location: . ');
    exit();
}
///end of F I L E AMEND BLOCK___________________________________________________________________
if (isset($_GET['p']) && is_numeric($_GET['p'])) {
    $pages = $_GET['p'];
} else { // counts all files
    $pages = 1;
    include CONNECT;

    $nwpsql = "SELECT COUNT(upload.id) as total from upload";
    if (preg_match("/client/i", $priv)) {
        $nwpsql .= " INNER JOIN usr on upload.userid = usr.id WHERE usr.email=:email";
        $nwpst->bindValue(":email", $_SESSION['email']);
    }
    $nwpst = $pdo->prepare($nwpsql);
    doPreparedQuery($nwpst, "Database error requesting the list of files:", false);
    $nwprow = $nwpst->fetch(PDO::FETCH_ASSOC);
    if (!$nwprow) {
        header("Location: ./?file_list");
        exit();
    }
    $records = $nwprow['total'];
    if ($records > $display) {
        $pages = ceil($records / $display);
    }
} //end of IF NOT PAGES SET
$sorter = array('f' => 'filename ASC', 'ff' => 'filename DESC', 'u' => 'name ASC', 'uu' => 'name DESC', 'uf' => 'name ASC, filename ASC', 'uuf' => 'name DESC, filename ASC',  'uff' => 'name ASC, filename DESC',  'uuff' => 'name DESC, filename DESC', 'ut' => 'name ASC, time ASC', 'utt' => 'name ASC, time DESC', 'uut' => 'name DESC, time ASC', 'uutt' => 'name DESC, time DESC', 't' => 'time ASC', 'tt' => 'time DESC');
$mainclass = $pages === 1 ? '' : 'paginate';
if (isset($_GET['s']) && is_numeric($_GET['s'])) {
    $start = $_GET['s'];
} else {
    $start = 0;
}

$sort = $_GET['sort'] ?? '1';

foreach ($sorter as $k => $v) {
    if ($k == $sort) break;
}
switch ($sort) {
    case $k:
        $order_by = $sorter[$k];
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
    include INCLUDES . 'find.php';
}

list($select, $from, $order) = selectUploaded($order_by, $start, $display);
//!!comes AFTER $select etc..
if (isset($_GET['action']) && $_GET['action'] === 'search') {
    if (!empty($_GET)) {
        include INCLUDES . 'search.inc.php';
        include_once TEMPLATE . 'head.html.php';
        include TEMPLATE . 'files.html.php';
        exit();
    } else {
        header("Location: .");
        exit();
    }
}
$nwpbuild = buildQuery($priv, 'ADMIN');
list($pdo, $nwpsql) = $nwpbuild($select, $from, $order);
$nwpst = doQuery($pdo, $nwpsql, 'Database error fetching files. ');
$nwprows = $nwpst->fetchAll(PDO::FETCH_ASSOC);

$files = array();
foreach ($nwprows as $nwprow) {
    $files[] = array(
        'id' => $nwprow['id'],
        'user' => $nwprow['name'],
        'client' => $nwprow['client'],
        'email' => $nwprow['email'],
        'filename' => $nwprow['filename'],
        'mimetype' => $nwprow['mimetype'],
        'description' => $nwprow['description'],
        'filepath' => $nwprow['filepath'],
        'file' => $nwprow['file'],
        'origin' => $nwprow['origin'],
        'time' => $nwprow['time'],
        'tel' => $nwprow['tel'],
        'size' => $nwprow['size']
    );
}
$error =  $lib[$_SERVER["QUERY_STRING"]] ?? '';

include TEMPLATE . 'head.html.php';
include TEMPLATE . 'files.html.php';
