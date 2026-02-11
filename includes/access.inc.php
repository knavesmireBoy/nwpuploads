<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/helpers.inc.php';

function databaseContainsUser($email, $password)
{
    include 'db.inc.php';
    $sql = "SELECT COUNT(*) FROM user INNER JOIN userrole ON user.id=userrole.userid WHERE email=:email AND password=:pwd";
    $st = $pdo->prepare($sql);
    $st->bindValue(":email", $email);
    $st->bindValue(":pwd", $password);
    doPreparedQuery($st, "<p>Error retrieving user:</p>");
    $result = $st->fetch(PDO::FETCH_NUM);

    if (empty($result)) {
        $error = 'Error retrieving user';
        include 'error.html.php';
        exit();
    }
    return true;
}

function userIsLoggedIn()
{
    if (isset($_POST['action']) && $_POST['action'] == 'login') {
        if (!isset($_POST['email']) or $_POST['email'] == '' or !isset($_POST['password']) or $_POST['password'] == '') {
            $GLOBALS['loginError'] = 'Please fill in both fields';
            return FALSE;
        }
        $password = md5($_POST['password'] . 'uploads');

        if (databaseContainsUser($_POST['email'], $password)) {
            session_start();
            $_SESSION['loggedIn'] = TRUE;
            $_SESSION['email'] = $_POST['email'];
            $_SESSION['password'] = $password;
            return TRUE;
        } else {
            session_start();
            unset($_SESSION['loggedIn']);
            unset($_SESSION['email']);
            unset($_SESSION['password']);
            $GLOBALS['loginError'] = 'The specified email address or password was incorrect.';
            return FALSE;
        }
    } //end of log in attempt

    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'logout') {
        session_start();
        unset($_SESSION['loggedIn']);
        unset($_SESSION['email']);
        unset($_SESSION['password']);
        //header("Location: " . $_POST['goto']);
        $e = $_GET['error'] ?? '';
        header("Location: ./?loginError=$e");
        exit();
    } //end of logout

    session_start();
    if (isset($_SESSION['loggedIn'])) {
        return databaseContainsUser($_SESSION['email'], $_SESSION['password']);
    }
} // end of user check

function userHasWhatRole()
{
    include 'db.inc.php';
    $sql = "SELECT userrole.roleid, user.id, count(*) as total FROM userrole INNER JOIN user ON user.id=userrole.userid where user.email=:email GROUP BY roleid";
    $email = $_SESSION['email'];
    $st = $pdo->prepare($sql);
    $st->bindValue(":email", $email);
    doPreparedQuery($st, "<p>Error establishing user role!:</p>");
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $error = 'Error establishing user role:';
       // echo "<h3>$error</h3>";
        header("Location: ./?action=logout&error=$error");
        exit();
    }
    return [$row['id'], $row['roleid']];
    return $roleplay;
}

function clientCheck($flag = false)
{
   // $lib = ["Client" => ['add', 'delete'], ""]
    
    list($key, $priv) = userHasWhatRole();
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
