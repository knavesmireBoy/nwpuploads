<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/helpers.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/access.inc.php';



$base = 'Log In';
$error = '';
$tmpl_error = '/nwp_uploads/includes/error.html.php';
$myip = '86.160.57.166';
$user_id = 0;
$text = '';
$suffix = '';
function getRemoteAddr()
{
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
        $ipAddress = array_pop(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
    }
    return $ipAddress;
}

function qsort($q)
{
    $res = explode($q, $_SERVER['QUERY_STRING']);
    $sort = isset($res[1]) ? $res[1] : '';
    $rest = isset($res[0]) ? $res[0] : '';
    return [$rest, $sort];
}

function qU($char)
{
    return function ($str) use ($char) {
        $l = strlen($str);
        $ret = '';
        if (!$l) {
            return $char;
        }
        $match = isset($str[0]) && $str[0] === $char;
        $nomatch = isset($str[0]) && $str[0] !== $char;
        if ($match) {
            $next = isset($str[1]) && $str[0] === $str[1];
            $ret = $next ? substr($str, 1) : $char . $str;
        } else if ($nomatch) {
            $ret = $char . $str;
        }
        return $ret;
    };
}

function q($char, $w)
{
    return function ($str) use ($char, $w) {
        $l = strlen($str);
        $ret = '';
        if (!$l) {
            return $char;
        }
        $match = isset($str[0]) && $str[0] === $char;
        $nomatch = isset($str[0]) && $str[0] !== $char;
        if ($match) {
            $next = isset($str[1]) && $str[0] === $str[1];
            $ret = $next ? substr($str, 1) : $char . $str;
        } else if ($nomatch) {
            $sanitize = preg_replace("/$char/", '', $str);
            $sanitize = preg_replace("/$w/", '', $sanitize);
            if (isset($sanitize[0])) {
                $str = preg_replace("/$sanitize[0]/", '', $str);
            }
            if (isset($str[0]) && $str[0] === $w) {
                $single = preg_match("/$char/", $str);
                $double = preg_match("/$char$char/", $str);
                $repl = preg_replace("/$char/", '', $str);
                $next = $double ? $char : ($single ? "$char$char" : $char);
                $ret =  $repl . $next;
            } else {
                return $char;
            }
        }
        return $ret;
    };
}

$mefiles = function ($arg) {
    return $_FILES['upload'][$arg];
};
if (isset($_GET['action']) && $_GET['action'] == 'download') {;
} else {
    //include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/base.html.php';
}

if (!userIsLoggedIn()) {
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/base.html.php';
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/login.html.php';
    exit();
}
//public page
if (!$roleplay = userHasWhatRole()) {
    $error = 'Only valid clients may access this page.';
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/accessdenied.html.php';
    exit(); // endof OBTAIN access level
} else {
    foreach ($roleplay as $key => $priv) { // $roleplay is an array, use foreach to obtain value and index
    }
    $domainstr = "RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))"; //!!?!! V. USEFUL VARIABLE IN GLOBAL SPACE
}


if (isset($_POST['action']) and $_POST['action'] == 'upload') {
    //Bail out if the file isn't really an upload
    if (!is_uploaded_file($_FILES['upload']['tmp_name'])) {
        $error = 'There was no file uploaded!';
        include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
        exit();
    }

    $uploadfile = $mefiles('tmp_name');
    $realname = $mefiles('name');


    $ext = preg_replace('/(.*)(\.[^0-9.]+$)/i', '$2', $realname);

    $time = time();
    //$uploadname = $time . getRemoteAddr() . $ext;
    $uploadname = $time . $ext;
    $path = '../../filestore/';
    $filedname =  $path . $uploadname;
    // Copy the file (if it is deemed safe)
    if (!copy($uploadfile, $filedname)) {
        $error = "Could not  save file as $filedname!";
        include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
        exit();
    }
    //echo $key;
    if ($priv == 'Admin' and !empty($_POST['user'])) { //ie Admin selects user
        $key = $_POST['user'];
        include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
        $sql = "SELECT domain FROM client WHERE domain='$key'";
        $st = doQuery($pdo, $sql, 'no domain');
        $row = $st->fetch(PDO::FETCH_NUM);
        if (count($row) > 0) {
            $sql = "SELECT employer.user_name, employer.user_id FROM (SELECT user.name AS user_name, user.id AS user_id, client.domain FROM user INNER JOIN client ON $domainstr =client.domain) AS employer WHERE employer.domain='$key' LIMIT 1"; //RETURNS one user, as relationship between file and user is one to one.
            //exit($sql);
            $st = doQuery($pdo, $sql, 'error retrieving details');
            $row = $st->fetch(PDO::FETCH_ASSOC);

            $key = $row['user_id'];

            if (!$key) {
                $key = $_POST['user']; //$key will be empty if above query returned empty set, reset
                $sql = "SELECT user.id from user INNER JOIN client ON user.client_id=client.id WHERE user.email='$key'";
                $st = doQuery($pdo, $sql, 'error retrieving details');
                $row = $st->fetch(PDO::FETCH_ASSOC);
                $key = $row['id'];
            } // @ clients use domain or full email as key if neither tests produce a result key refers to a user only
        } //END OF COUNT
    }

    // Prepare user-submitted values for safe database insert
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
    $uploaddesc = isset($_POST['desc']) ? $_POST['desc'] : '';
    $size =  $mefiles('size') / 1024;
    $sql = "INSERT INTO upload SET
    filename = :realname,
    mimetype = :uploadtype,
    description = :uploaddesc,
    filepath = :pth,
    file = :uploadname,
    size = :sized
    userid=:id
    time=NOW()";

    $sql = "INSERT INTO upload (filename, mimetype, description, filepath, file, size, userid, time) VALUES(:realname, :uploadtype,:uploaddesc,:pth,:uploadname,:sized,:userid, NOW())";
    $st = $pdo->prepare($sql);
    $st->bindValue(":realname", $realname);
    $st->bindValue(":uploadtype", $mefiles('type'));
    $st->bindValue(":uploaddesc", $uploaddesc);
    $st->bindValue(":pth", $path);
    $st->bindValue(":uploadname", $uploadname);
    $st->bindValue(":sized", $size);
    $st->bindValue(":userid", $key);

    $res = doPreparedQuery($st, "<p>Database error storing file information!</p>");
    /*
    $sql2 = "select user.whatever from user INNER JOIN upload ON user.id=upload.userid INNER JOIN (SELECT MAX(id) AS big FROM upload) AS last ON last.big = upload.id";
NOT REQUIRED - USING mysqli_INSERT_ID INSTEAD - BUT KEPT AS AN EXAMPLE OF A SUBQUERY
*/
    $menum = $pdo->lastInsertId();
    $sql = "select user.email, user.name, upload.id, upload.filename from user INNER JOIN upload ON user.id=upload.userid WHERE upload.id=$menum";
    $st = doQuery($pdo, $sql, 'Error selecting email address.');
    $row = $st->fetch(PDO::FETCH_ASSOC);

    $email = $row['email'];
    $file = $row['filename'];
    $name = $row['name'];

    if ($priv == 'Admin') {
        $body =  'We have just uploaded the file' . $file . 'for checking.';
        $body = wordwrap($body, 70);
        //mail($email, $file, $body, "From: $name <{$_SESSION['email']}>");
    }

    /*
else {
$body =  '<html><body><p>We have just uploaded the file <a href='.
'"http://northwolds.serveftp.net/nwp_uploads/" /><strong>' . $file . '</strong></a> for printing.</p></body></html>'; 
if (!@mail('north.wolds@btinternet.com', 'Files to North Wolds | ' . $file,  
   $body,  
    "From: $name <{$_SESSION['email']}>\n" . 
     "cc:  $name <files@northwolds.co.uk>\n" .
    "MIME-Version: 1.0\n" .  
    "Content-type: text/html; charset=iso-8859-1"))
{
 exit('<p>The file uploaded but an email could not be sent.</p>');  
}
}
*/
    header('Location: .');
    exit();
} // end of upload_____________________________________________________________________


if (isset($_GET['action']) and isset($_GET['id'])) {

    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
    $sql = "SELECT filename, mimetype, filepath, file, size FROM upload WHERE id =:id";
    $st = $pdo->prepare($sql);
    $st->bindValue(":id", $_GET['id']);
    doPreparedQuery($st, '<p>Database error fetching requested file.</p>');
    $file = $st->fetch(PDO::FETCH_ASSOC);

    if (!$file) {
        $error = 'File with specified ID not found in the database!';
        include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
        exit();
    }
    $filename = $file['filename'];
    $mimetype = $file['mimetype'];
    $filepath = $file['filepath'];
    $uploadfile = $file['file'];
    $size = $file['size'];
    $filepath .= $uploadfile;
    $fullpath = $_SERVER['DOCUMENT_ROOT'] . $filepath;
    $filedata = file_get_contents($fullpath);
    $disposition = 'inline';
    //$mimetype = 'application/x-unknown'; application/octet-stream

    if ($_GET['action'] == 'download') {
        $disposition = 'attachment';
    }
    //Content-type must come before Content-disposition
    header("Content-type: $mimetype");
    header('Content-disposition: ' . $disposition . '; filename=' . '"' . $filename . '"'); //this works
    //header("Content-Transfer-Encoding: binary");
    header('Content-length:' . strlen($filedata));
    echo $filedata;
    exit();
} // end of download

if (isset($_POST['action']) and $_POST['action'] == 'delete') {
    $id = $_POST['id'];
    $title = "Prompt";
    $prompt = "Are you sure you want to delete this file? ";
    $call = "confirm";
    $pos = "Yes";
    $neg = "No";
    $action = '';
}

if (isset($_POST['confirm']) and $_POST['confirm'] == 'Yes') {
    $prompt = "Select the extent of deletions";
    $id = $_POST['id'];
    $del = "proceed";
}

if (isset($_POST['proceed']) and $_POST['proceed'] == 'remove') {
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';

    $id = $_POST['id'];
    $path = '../../filestore/';

    if ($_POST['extent'] == "c") {
        $sql = "SELECT c.file FROM user INNER JOIN client ON user.client_id = client.id INNER JOIN upload AS c ON user.id = c.userid  INNER JOIN upload AS d ON d.userid=user.id WHERE d.id=$id";
    } elseif ($_POST['extent'] == "u") {
        $sql = "SELECT upload.file FROM upload INNER JOIN user ON upload.userid=user.id INNER JOIN upload AS d ON upload.userid=d.userid WHERE d.id=$id";
    } elseif ($_POST['extent'] == "f") {
        $sql = "SELECT file FROM upload WHERE id=$id";
    } else {
        header('Location: .');
        exit();
    }

    $st = doQuery($pdo, $sql, 'Error failed to delete file');

    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        $error = 'Database error fetching stored files.';
        include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
        exit();
    }

    $sql = "DELETE FROM upload WHERE file=:f";
    $st = $pdo->prepare($sql);
    foreach ($rows as $row) {
        $file = $row['file'];
        $st->bindValue(":f", $file);
        $res = doPreparedQuery($st, '<p>Error deleting file.</p>');
        if (!$res) { //delete file ref
            $error = 'Error deleting file.';
            include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
            exit();
        }
        $thepath = $path . $file;
        unlink($thepath);
    }
    header('Location: .');
    exit();
} //________________________end of confirm/delete

if (isset($_POST['confirm']) and $_POST['confirm'] == 'No') { //swap
    $prompt = "Change ownership on ALL files?";
    $id = $_POST['id'];
    $swap = "swap";
    $call = "swap";
    $pos = "Yes";
    $neg = "No";
    $action = '';
}

if (isset($_POST['swap'])) { //SWITCH OWNER OF FILE OR JUST UPDATE DESCRIPTION (FILE AMEND BLOCK)
    $colleagues = [];
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';




    $answer = $_POST['swap'];
    $email = $_SESSION['email'];

    if ($priv == 'Admin') {
        $sql = "SELECT upload.id, filename, description, upload.userid, user.name FROM upload INNER JOIN user ON upload.userid=user.id  WHERE upload.id=:id";

        $st = $pdo->prepare($sql);
        $st->bindValue(":id", $_POST['id']);
        doPreparedQuery($st, '<p>Database error fetching stored files.</p>');
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $error = 'Database error fetching stored files.';
            include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
            exit();
        }
        $filename = $row['filename'];
        $diz = $row['description'];
        $userid = $row['userid'];
        $aname = $row['name'];
        $button = "Update";
        $action = '';
        $answer = $_POST['swap'];

        $sql = "SELECT employer.id, employer.name FROM upload INNER JOIN user ON upload.userid = user.id INNER JOIN (SELECT user.id, user.name, client.domain FROM user INNER JOIN client ON $domainstr=client.domain) AS employer ON $domainstr=employer.domain WHERE upload.id=:id ORDER BY name"; //colleagues
        $st = $pdo->prepare($sql);
        $st->bindValue(":id", $row['id']);
        doPreparedQuery($st, 'Database error fetching colleagues.');
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        //exit($sql_col);
        if (empty($rows)) {
            $error = 'Database error fetching colleagues.';
            include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
            exit();
        }

        foreach ($rows as $row) {
            $colleagues[$row['id']] = $row['name'];
        }
        if (empty($colleagues)) {
            $sql = "SELECT user.name, user.id FROM user LEFT JOIN client ON user.client_id=client.id  WHERE client.domain IS NULL UNION SELECT user.name, user.id FROM user INNER JOIN client ON user.client_id=client.id ORDER BY name";
            $st = doQuery($pdo, $sql, 'Database error fetching users.');
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) {
                $error = 'Database error fetching users.';
                include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
                exit();
            }
            foreach ($rows as $row) {
                $all_users[$row['id']] = $row['name'];
            }
        }
    } //if
    else {
        header('Location: . ');
        exit();
    }
} ///

if (isset($_POST['original'])) { //CAN ONLY BE SET BY ADMIN, 'original' is common to both options of file amend block
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
    $user = isset($_POST['colleagues']) ? $_POST['colleagues'] : (isset($_POST['user']) ? $_POST['user'] : $_POST['original']);
    $id =  intval($_POST['fileid']);
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
        $st->bindValue(':fileid', $id);
    }
    doPreparedQuery($st, '<p>Error Updating Details!</p>');
    header('Location: . ');
    exit();
}
///end of F I L E AMEND BLOCK___________________________________________________________________

//a default block___________________________________________________________________

$display = 10;
if (isset($_GET['p']) and is_numeric($_GET['p'])) {
    $pages = $_GET['p'];
} else { // counts all files
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
    $sql = "SELECT COUNT(upload.id) as total from upload ";
    if ($priv == 'Client') {
        $sql .= " INNER JOIN user on upload.userid = user.id WHERE user.email=:email";
    }

    $st = $pdo->prepare($sql);
    $st->bindValue(":email", $_SESSION['email']);
    doPreparedQuery($st, "<p>Database error fetching requesting THE list of files:</p>");
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $error = 'Database error fetching requesting THE list of files.';
        include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
        exit();
    }

    $records = $row['total'];
    if ($records > $display) {
        $pages = ceil($records / $display);
    } else $pages = 1; //INITIAL SETTING OF PAGES
} //end of IF NOT PAGES SET

if (isset($_GET['s']) and is_numeric($_GET['s'])) {
    $start = $_GET['s'];
} else {
    $start = 0;
}



$meswitch = array('f' => 'filename ASC', 'ff' => 'filename DESC', 'u' => 'user ASC', 'uu' => 'user DESC', 'uf' => 'user ASC, filename ASC', 'uuf' => 'user DESC, filename ASC',  'uff' => 'user ASC, filename DESC',  'uuff' => 'user DESC, filename DESC', 'ut' => 'user ASC, time ASC', 'utt' => 'user ASC, time DESC', 'uut' => 'user DESC, time ASC', 'uutt' => 'user DESC, time DESC', 't' => 'time ASC', 'tt' => 'time DESC');

$sort = (isset($_GET['sort']) ? $_GET['sort'] : '1');


foreach ($meswitch as $ix => $u) {
    if ($ix == $sort) break;
}
switch ($sort) {
    case $ix:
        $order_by = $meswitch[$ix];
        break;
    default:
        $order_by = 'time DESC';
        $sort = 'tt';
        break;
}


//D I S P L A Y_______________________________________________________________
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php'; ///Present list of users for administrators
$sqlu = "SELECT user.id, user.name FROM user LEFT JOIN client ON user.client_id=client.id WHERE client.domain IS NULL ORDER BY name";

$st = doQuery($pdo, $sqlu, "<p>Error retrieving details</p>");
$result = $st->fetchAll(PDO::FETCH_ASSOC);
if (!$result) {
    $error = 'Database error fetching users.';
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
    exit();
}
foreach ($result as $row) {
    $users[$row['id']] = $row['name'];
}
/*
$sqlc ="SELECT employer.user_id, employer.name from
(SELECT user.name, user.id as user_id, client.domain FROM user INNER JOIN client ON RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))=client.domain) AS employer";
*/

$sqlc = "SELECT name, domain, tel FROM client ORDER BY name";
$st = doQuery($pdo, $sqlc, "<p>Database error fetching clients.</p>");
$result = $st->fetchAll(PDO::FETCH_ASSOC);
if (!$result) {
    $error = 'Database error fetching clients.';
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
    exit();
}
foreach ($result as $row) {
    $client[$row['domain']] = $row['name'];
}
//end of default_______________________________________________________________________

if (isset($_GET['find'])) {
    if ($priv != "Admin"): //CUSTOMISES SELECT MENU
        $email = "{$_SESSION['email']}";
        include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
        $sql = "SELECT $domainstr  FROM user WHERE user.email='$email'";
        $result = mysqli_query($link, $sql);
        $row = mysqli_fetch_array($result);
        $dom = $row[0];
        $sql = "SELECT COUNT(*) AS dom FROM user INNER JOIN client ON $domainstr=client.domain WHERE $domainstr='$dom' AND client.domain='$dom'";
        $result = mysqli_query($link, $sql);
        $row = mysqli_fetch_array($result);
        $count = $row['dom'];
        if (count($count) > 0) {
            $where = " WHERE user.email='$email'"; //client
        } else {
            $where = " WHERE user.id=$key"; //user
        }
        $sql = "SELECT employer.id, employer.name  FROM user INNER JOIN (SELECT user.id, user.name, client.domain FROM user INNER JOIN client ON $domainstr=client.domain) AS employer ON $domainstr=employer.domain $where";
        $result = mysqli_query($link, $sql);
        if (!$result) {
            $error = 'Database error fetching clients.';
            include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
            exit();
        }
        $users = array(); //resets user array to display users of current client
        while ($row = mysqli_fetch_array($result)) {
            $users[$row['id']] = $row['name'];
        }
        if ($count <= 1) { //SELECT MENU in SEARCH for only more than one "employee"
            $users = array();
            $zero = true;
        }
        $client = array();
    endif;
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/base.html.php';
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/search.html.php';
    exit();
}
/// S E A R C H  M E !!

//INITIAL FILE SELECTION
/*SELECT id, substr(`name`,(length(`name`) - locate(' ', reverse(`name`))+1)+1)
AS `surname`
FROM `user`
ORDER BY `surname` ASC
*/


//_______//_______//_______//_______//_______//_______//_______//_______//_______//_____
$select = "SELECT upload.id, filename, mimetype, description, filepath, file, size, time,  MID(file, 11, 14) AS origin, user.email";
$from = " FROM upload INNER JOIN user ON upload.userid=user.id";
$order = " ORDER BY $order_by LIMIT $start, $display";
$domainstr = "RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))";
//_______//_______//_______//_______//_______//_______//_______//_______//_______//_____

if (isset($_GET['action']) and $_GET['action'] == 'search') {
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';

    $tel = '';
    $from .= " INNER JOIN userrole ON user.id=userrole.userid";
    $user_id =  $_GET['user'];

    if ($priv == 'Admin') {
        //will either return empty set(no error) or produce count. Test to see if a client has been selected.
        $sql = "SELECT domain FROM client WHERE domain=:id";
        $st = $pdo->prepare($sql);
        $st->bindValue(":id", $user_id);
        doPreparedQuery($st, "<p>Unable to find domain</p>");
        $dom = $st->fetch(PDO::FETCH_NUM);
        //user_id is text(domain) for Clients
        if ($dom) {
            $from .= " INNER JOIN client ON $domainstr = client.domain ";
            $where = " WHERE domain=:uid";
            $check = count($row[0]);
        } else {
            $where = ' WHERE TRUE';
        }
        $select .= ", user.name as user";
    } //admin
    else {
        $email = $_SESSION['email'];
        $where .= " WHERE user.email=:email";
    }
    if ($user_id != '') { // A user is selected 
        if (!isset($check)) $where .= " AND user.id=:uid";
    }
    $text = $_GET['text'];
    if ($text != '') { // Some search text was specified 
        $where .= " AND upload.filename LIKE '%$text%'";
    }
    $suffix = $_GET['suffix'];
    if (isset($suffix)) {
        if ($suffix == 'owt') {
            $where .= " AND (upload.filename NOT LIKE '%pdf' AND upload.filename NOT LIKE '%zip')";
        } elseif ($suffix == 'pdf' or $suffix == 'zip') {
            $where .= " AND upload.filename LIKE '%$suffix'";
            //$where .= sprintf(" AND upload.filename LIKE %s", GetSQLValueString('%'.$suffix, "text"));//Tricky percent symbol
        }
    }

    $sql =  $select . $from . $where . $order;
    $st = doQuery($pdo, $sql, '<p>Error fetching file details.</p>');
    $res = $st->fetch();
    if (empty($res)) {
        $error = 'Error fetching file details.' . $sql;
        include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
        exit();
    }
    $where .= " GROUP BY upload.id ";
    $sqlcount = $select . ', COUNT(upload.id) as total ' . $from . $where . $order;
    $st =  doQuery($pdo, $sqlcount, '<p>Error getting file count, innit</p>');
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        $error = 'Error getting file count.';
        include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
        exit();
    }
    $records = $rows[0]['total'];
    if ($records > $display) {
        $pages = ceil($records / $display);
    } else $pages = 1;

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
            'size' => $row['size']
        );
    }
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/base.html.php';
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/files.html.php';
    exit();
}
//ENDEND S E A R C H//ENDEND S E A R C H//ENDEND S E A R C H//ENDEND S E A R C H

if ($priv == 'Admin') {
    /*by surname
    $select .= ", substr(user.name, (length(user.name) - locate(' ', reverse(user.name)) +1) +1 ) AS `user`";
    */
    $select .= ", user.name as user"; //append to line 465(ish)
    $from .= " INNER JOIN userrole ON user.id=userrole.userid";
    $where  = ' WHERE TRUE';
    $ext = isset($_GET['ext']) ? $_GET['ext'] : null;
    $getuser = isset($_GET['u']) ? $_GET['u'] : '';
    $textme = isset($_GET['t']) ? $_GET['t'] : '';
    $useroo = isset($useroo) ? $useroo : $getuser;
    if ($ext) {
        if ($ext == 'owt') {
            $where .= " AND (upload.filename NOT LIKE '%pdf' AND upload.filename NOT LIKE '%zip')";
        } else $where .= " AND upload.filename LIKE '%$ext'";
    }
    if (isset($useroo) && is_numeric($useroo)) { //CLIENTS USE EMAIL DOMAIN AS ID THERFORE NOT A NUMBER
        if ($useroo = $getuser) {
            $where .= " AND user.id=$useroo";
        }
    } else {
        if ($getuser) {
            $where .= " AND $domain='$getuser'";
        }
    }
    if ($textme) {
        $where .= " AND upload.filename LIKE '%$textme%'";
    }
} //admin

else {
    $email = $_SESSION['email'];
    $from .= " INNER JOIN userrole ON user.id=userrole.userid";
    //$where .=" WHERE user.email='$email' ";
    $where = " WHERE user.email='$email' ";
}
//$sql= $select . $from . $where . $order; //DEFAULT; TELEPHONE BLOCK REQUIRED TO OBTAIN CLIENT PHONE NUMBER
$sql = $select;
$select_tel = ", client.tel";
$from .= " LEFT JOIN client ON user.client_id=client.id"; //note LEFT join to include just 'users' also
$sql .= $select_tel . $from . $where . $order;
//___________________________________________________________________________________________END OF TELEPHONE
$st = doQuery($pdo, $sql, 'Database error fetching files. ');
$result = $st->fetchAll(PDO::FETCH_ASSOC);
$files = array();
foreach ($result as $row) {
    $files[] = array(
        'id' => $row['id'],
        'user' => (isset($row['user'])) ? $row['user'] : '',
        'email' => $row['email'],
        'filename' => $row['filename'],
        'mimetype' => $row['mimetype'],
        'description' => $row['description'],
        'filepath' => $row['filepath'],
        'file' => $row['file'],
        'origin' => $row['origin'],
        'time' => $row['time'],
        'tel' => $row['tel'], // ONLY REQUIRED FOR TELEPHONE BLOCK
        'size' => $row['size']
    );
}
$base = 'North Wolds Printers | The File Uploads';

$a = [null, 'empty', 'single', 'double'];
//STATE is UPPER EVENT is lower ie Uu Tu
//string can contain up to 2 instructions eg ut, uutt
//a repeat instruction will toggle u -> uu; uu -> u
//instructions can be repeated up to two times
//a FOREIGN event ON another state RESETS except..
//U STATE allows for ONE further instruction ut NOT utf, a 3rd instruction resets ut -> f
//
//t an f events are subservient to u ie Ut; Uff NOT fu OR ttu
//there can be no ft combo an f event on a t state will RESET tt -> f and vice versa (vv)
//U STATE toggle u/uu; append f or t;  toggle f/ff t/tt; RESET on THIRD
//T STATE toggle t/tt; RESET on NON t
//F STATE toggle f/f; RESET on NON f
//$data = "?sort=";
//first test: $i = strpos($q, "=");
//$i = $i ? $i + 1 : $i;
//$sort = $i ? substr($data, $i) : $q . <EVENT>;

list($qs, $state) = qsort('sort=');


$ufn = qU('u');
$tfn = q('t', 'u');
$ffn = q('f', 'u');
$tmp = $qs ? "&sort=" : "?sort=";
$qs = $qs ? "?$qs" : '';
$qs = $qs . $tmp;
$qs = preg_replace("/&&/", "&", $qs);
$fhead = $qs . $ffn($state);
$uhead = $qs . $ufn($state);
$thead = $qs . $tfn($state);

/*
$_SERVER["REQUEST_URI"] = 'https://www.amazon.co.uk/gp/video/detail/amzn1.dv.gti.0ab7e668-f22e-12a9-025b-fb626ce88bd9?ref_=imdbref_tt_ov_wbr_ovf__pvs_piv&tag=imdbtag_tt_ov_wbr_ovf__pvs_piv-21';
*/

include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/base.html.php';
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/files.html.php';
