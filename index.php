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
$lib = ['nofile' => "<h4>'There was no file uploaded!'</h4>", 'fetch_files' => '<h4>Database error fetching stored files.</h4>', 'delete_file' => '<h4>Error deleting file.</h4>', 'file_list' => '<h4>Database error requesting the list of files.</h4>'];
$clientlist = null;
$display = 10;
$template = '/upload.html.php';

$uploaded = function ($arg) {
    return $_FILES['upload'][$arg];
};

if (!userIsLoggedIn()) {
    include __DIR__ . '/templates/login.html.php';
    exit();
}
//public page
if ($roleplay = userHasWhatRole()) {
    list($key, $priv) = $roleplay;
    $domainstr = "RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))"; //!!?!! V. USEFUL VARIABLE IN GLOBAL SPACE
} else {
    $error = 'Only valid clients may access this page.';
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/accessdenied.html.php';
    exit(); // endof OBTAIN access level
}

if (isset($_POST['action']) && $_POST['action'] == 'upload') {
    //Bail out if the file isn't really an upload
    if (!is_uploaded_file($_FILES['upload']['tmp_name'])) {
        header("Location: ./?nofile");
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
        $error = "Could not  save file as $filedname!";
        include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/error.html.php';
        exit();
    }
    if ($priv == 'Admin' and !empty($_POST['user'])) { //ie Admin selects user
        $key = $_POST['user'];
        include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
        $st = $pdo->prepare("SELECT domain FROM client WHERE domain=:id");
        $st->bindValue(":id", $key);
        doPreparedQuery($st, 'Error fetching domain');
        $row = $st->fetch(PDO::FETCH_NUM);
        if ($row && count($row) > 0) {
            $sql = "SELECT employer.user_name, employer.user_id FROM (SELECT user.name AS user_name, user.id AS user_id, client.domain FROM user INNER JOIN client ON $domainstr =client.domain) AS employer WHERE employer.domain=:id LIMIT 1";
            //RETURNS one user, as relationship between file and user is one to one.
            //exit($sql);
            $st = $pdo->prepare($sql);
            $st->bindValue(":id", $key);
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
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
    $uploaddesc = isset($_POST['desc']) ? $_POST['desc'] : '';
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
    /*
    "select user.whatever from user INNER JOIN upload ON user.id=upload.userid INNER JOIN (SELECT MAX(id) AS big FROM upload) AS last ON last.big = upload.id";
    NOT REQUIRED - USING mysqli_INSERT_ID INSTEAD - BUT KEPT AS AN EXAMPLE OF A SUBQUERY
    */
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
    if (!file_exists($filepath)) {
        header("Location: .");
        exit();
    }
    $filedata = file_get_contents($fullpath);
    $disposition = 'inline';
    //$mimetype = 'application/x-unknown'; application/octet-stream
    if ($_GET['action'] == 'download') {
        $disposition = 'attachment';
    }
    //Content-type must come before Content-disposition
    header("Content-type: $mimetype");
    //this works..
    header('Content-disposition: ' . $disposition . '; filename=' . '"' . $filename . '"');
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
    $template = '/prompt.html.php';
}

if (isset($_POST['confirm']) and $_POST['confirm'] == 'Yes') {
    $prompt = "Select the extent of deletions";
    $id = $_POST['id'];
    $del = "proceed";
    $template = '/prompt.html.php';
}

if (isset($_POST['proceed']) and $_POST['proceed'] == 'remove') {
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
    $path = '../../filestore/';

    if ($_POST['extent'] == "c") {
        $sql = "SELECT c.file FROM user INNER JOIN client ON user.client_id = client.id INNER JOIN upload AS c ON user.id = c.userid INNER JOIN upload AS d ON d.userid=user.id WHERE d.id=:id";
    } elseif ($_POST['extent'] == "u") {
        $sql = "SELECT upload.file FROM upload INNER JOIN user ON upload.userid=user.id INNER JOIN upload AS d ON upload.userid=d.userid WHERE d.id=:id";
    } elseif ($_POST['extent'] == "f") {
        $sql = "SELECT file FROM upload WHERE id=:id";
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
        $res = doPreparedQuery($st, '<p>Error deleting file.</p>');
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

if (isset($_POST['confirm']) and $_POST['confirm'] == 'No') { //swap
    $prompt = "Change ownership on ALL files?";
    $id = $_POST['id'];
    $swap = "swap";
    $call = "swap";
    $pos = "Yes";
    $neg = "No";
    $action = '';
    $template = '/prompt.html.php';
}

//SWITCH OWNER OF FILE OR JUST UPDATE DESCRIPTION (FILE AMEND BLOCK)
if (isset($_POST['swap'])) {
    // $colleagues = [];
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
    $template = '/update.html.php';
    $answer = $_POST['swap'];
    $email = $_SESSION['email'];


    $sql = "SELECT upload.id, filename, description, upload.userid, user.name FROM upload INNER JOIN user ON upload.userid=user.id  WHERE upload.id=:id";
    $st = $pdo->prepare($sql);
    $st->bindValue(":id", $_POST['id']);
    doPreparedQuery($st, '<p>Database error fetching stored files.</p>');
    $row = $st->fetch(PDO::FETCH_ASSOC);

    $filename = $row['filename'];
    $diz = $row['description'];
    $userid = $row['userid'];
    $aname = $row['name'];
    $button = "Update";
    $action = '';
    $answer = $_POST['swap'];
    $rows = [];
    $id =  $_POST['id']; //CRUCIAL to pass id to file amend form (update.html.php)

    if ($priv == 'Client') {
        $sql = "SELECT employer.id, employer.name FROM upload INNER JOIN user ON upload.userid = user.id INNER JOIN (SELECT user.id, user.name, client.domain FROM user INNER JOIN client ON $domainstr=client.domain) AS employer ON $domainstr=employer.domain WHERE upload.id=:id ORDER BY name"; //colleagues
        $st = $pdo->prepare($sql);
        $st->bindValue(":id", $row['id']);
        doPreparedQuery($st, 'Database error fetching colleagues.');
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $colleagues[$row['id']] = $row['name'];
        }
    }

    if ($priv == 'Admin') {
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
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
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
//a default block___________________________________________________________________

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
    doPreparedQuery($st, "<p>Database error  requesting THE list of files:</p>");
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        header("Location: ./?file_list");
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
    $template = '/search.html.php';
    if ($priv != "Admin"): //CUSTOMISES SELECT MENU overwriting DEFAULT $client and $users
        $email = $_SESSION['email'];
        $iskey = false;
        include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
        $sql = "SELECT $domainstr FROM user WHERE user.email=:email";
        $st = $pdo->prepare($sql);
        $st->bindValue(":email", $email);
        doPreparedQuery($st, "<p>Error finding domain</p>");
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
        if (!empty($rows)) {
            $users = array(); //resets user array to display users of current client
            foreach ($rows as $row) {
                $users[$row['id']] = $row['name'];
            }
        }
        //SELECT MENU in SEARCH for only more than one "employee"
        else {
            $users = array();
            $zero = true;
        }
        $client = array();
    endif;

   
   // include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/base.html.php';
  //  include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/search.html.php';
  //  exit();
}
/// S E A R C H  M E !!

//_______//_______//_______//_______//_______//_______//_______//_______//_______//_____
$select = "SELECT upload.id, filename, mimetype, description, filepath, file, size, time,  MID(file, 11, 14) AS origin, user.email, user.name";
$from = " FROM upload INNER JOIN user ON upload.userid=user.id";
$order = " ORDER BY $order_by LIMIT $start, $display";
$domainstr = "RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))";
//_______//_______//_______//_______//_______//_______//_______//_______//_______//_____

if (isset($_GET['action']) and $_GET['action'] == 'search') {
    include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/db.inc.php';
    $tel = '';
    $from .= " INNER JOIN userrole ON user.id=userrole.userid";
    $user_id =  $_GET['user'];
    $select .= ", user.name as user";
    if ($priv == 'Admin') {
        //will either return empty set(no error) or produce count. Test to see if a client has been selected.
        $sql = "SELECT domain FROM client WHERE domain=:id";
        $st = $pdo->prepare($sql);
        $st->bindValue(":id", $user_id);
        doPreparedQuery($st, "<p>Unable to find domain</p>");
        $row = $st->fetch(PDO::FETCH_NUM);
        //user_id is text(domain) for Clients
        if ($row) {
            $dom = $row[0];
            $from .= " INNER JOIN client ON $domainstr = client.domain ";
            $where = " WHERE domain='$dom'";
            $check = count($row);
        } else {
            $where = ' WHERE TRUE';
        }
    } //admin
    else {
        $email = $_SESSION['email'];
        $where = " WHERE user.email='$email'";
    }
    if ($user_id != '') { // A user is selected 
        if (!isset($check)) $where .= " AND user.id='$user_id'";
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

    $where .= " GROUP BY upload.id ";
    $sqlcount = $select . ', COUNT(upload.id) as total ' . $from . $where . $order;

    $st =  doQuery($pdo, $sqlcount, '<p>Error getting file count, innit</p>');
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $records = empty($rows) ? 0 : $rows[0]['total'];
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

//______________________________________________END OF TELEPHONE
$st = doQuery($pdo, $sql, 'Database error fetching files. ');
$result = $st->fetchAll(PDO::FETCH_ASSOC);
$files = array();
foreach ($result as $row) {
    $files[] = array(
        'id' => $row['id'],
        //'user' => (isset($row['user'])) ? $row['user'] : '',
        'user' => $row['name'],
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

$arr = get_defined_vars();
foreach ($arr as $key => $value) {
    if (preg_match('/_[A-Z]+/', $key)) unset($arr[$key]);
}

include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/base.html.php';
$error =  $lib[$_SERVER["QUERY_STRING"]] ?? '';
ob_start();
include $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/templates/files.html.php';
