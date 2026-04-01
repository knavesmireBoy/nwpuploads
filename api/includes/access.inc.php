<?php
//include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/api/includes/helpers.inc.php';

function databaseContainsUser($email, $password)
{
    include 'db.inc.php';
    $sql = "SELECT password FROM usr INNER JOIN userrole ON usr.id=userrole.userid WHERE email=:email AND password=:pwd";
    $st = $pdo->prepare($sql);
    $st->bindValue(":email", $email);
    $st->bindValue(":pwd", $password);
    doPreparedQuery($st, "Error retrieving user:");
    $result = $st->fetch(PDO::FETCH_ASSOC);
    if (empty($result)) {
        $error = 'Error retrieving user';
        include TEMPLATE . 'head.html.php';
        include TEMPLATE . 'error.html.php';
       // exit();
    }
    return true;
}

function userIsLoggedIn()
{
    if (isset($_POST['action']) && $_POST['action'] == 'login') {
        if (empty($_POST['email']) || empty($_POST['password'])) {
            $GLOBALS['loginerror'] = 'Please fill in both fields';
            return FALSE;
        }
        $password = md5($_POST['password'] . 'uploads');

        if (databaseContainsUser($_POST['email'], $password)) {
            session_start();
            $_SESSION['loggedIn'] = TRUE;
            $_SESSION['email'] = trim($_POST['email']);
            $_SESSION['password'] = $password;
            return TRUE;
        } else {
            session_start();
            dump($_SESSION);
            unset($_SESSION['loggedIn']);
            unset($_SESSION['email']);
            unset($_SESSION['password']);
            $GLOBALS['loginerror'] = 'The specified email address or password was incorrect.';
            return FALSE;
        }
    } //end of log in attempt

    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'logout') {
        $location = "Location: .";
        session_start();
        unset($_SESSION['loggedIn']);
        unset($_SESSION['email']);
        unset($_SESSION['password']);
        setExtent(0);
        $setcookie = doSetCookie(false);
        $setcookie('email', $_POST['email']);
        $setcookie('username', $_POST['name']);
        $e = $_GET['error'] ?? '';
        $location .= $e ? "/?loginerror=$e" : '';
        header($location);
        exit();
    } //end of logout
    session_start();
    if (isset($_SESSION['loggedIn'])) {
        return databaseContainsUser($_SESSION['email'], $_SESSION['password']);
    }
} // end of user check

function obtainUserRole($flag = false)
{
    include 'db.inc.php';
    $sql = "SELECT usr.id, userrole.roleid FROM userrole INNER JOIN usr ON usr.id=userrole.userid where usr.email=:email";
    if ($flag) {
        $sql = "SELECT usr.id, userrole.roleid FROM usr INNER JOIN userrole ON usr.id=userrole.userid WHERE (userrole.roleid LIKE 'Client%' OR userrole.roleid LIKE '%Admin') AND usr.email=:email";
    }
    $email = $_SESSION['email'];
    $st = $pdo->prepare($sql);
    $st->bindValue(":email", $email);
    doPreparedQuery($st, "");
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }
    return [$row['id'], $row['roleid']];
}

function clientCheck($flag = false)
{
    list($key, $priv) = obtainUserRole();
    $c = preg_match("/client/i", $priv);
    $ca = $c && preg_match("/admin/i", $priv);
    return $flag ? !($ca || !$c) : ($ca || !$c);
}
/*
function email($em, $id) {
$email = html($em);
$body = "Before I answer that question, lets look at the alternative method your printer is suggesting. In InDesign and all the Creative Suite applications, it s easy to create a PostScript file from the Print dialog box. Just select PostScript from the Printer menu at the top of the dialog box. Then you can choose a PPD file (I d suggest selecting your Adobe PDF printer if you have Acrobat) or Device Independent (removing any printer dependencies, useful for some postprocessing workflows like imposition). Make your choices in the Print dialog box, and then click Save instead of Print to create a PostScript file. You process that PostScript file in Distiller using the PDF settings file your printer suggests.";
$body = wordwrap($body, 70);
return mail($email, 'File upload complete', $body, "From: {$_SESSION['email']}");
}
*/

function email($em, $id)
{
    echo $em;
}

function setExtent($count)
{
    session_start();
    if (is_int($count)) {
        $_SESSION['extent'] = $count;
    } else if (isset($_SESSION['extent'])) {
        unset($_SESSION['extent']);
    }
}
