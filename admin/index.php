<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/nwp_uploads/includes/access.inc.php';

function query()
{
  $lib = ['nousers' => "<h4>Unable to find any users</h4>", "addnotice" => "Please fill required fields", "selectuser" => "Please select a user for editing", "clientflag" => "Cannot assign this user to a new client", "lastuser" => "To remove this last user, please delete the client instead",  "lastclient" => "You do not have the privileges to remove your details from the database, please contact the database administrator", "denied" => "You do not have the required privileges to delete, please contact your administrator", "access" => "You do not have the privileges to add a user", "deniedbyadmin" => "Cannot delete this user until a new client admin role is assigned to this client", "self" => "Only a peer can perform this deletion", "freelancer" => "Cannot assign this domain", 'addno' => 'You do not have the required privilges to add a user'];
  $query = explode('=', $_SERVER["QUERY_STRING"]);
  $q = $query[1] ?? $query[0];
  return $lib[$q] ?? NULL;
}


$super = "andrewsykes@btinternet.com";
$users = [];
$id = $_GET['edit'] ?? '';
$error = query();
$pagehead = "Edit details";
$message = $error ?? '';
$denied = false;
$usercount = 0;
setExtent(null);
$selected = null;
$goto = '.';
$pageid = 'admin_user';
$roleorder = ['Browser', 'Manager', 'Client', 'Client Admin', 'Admin'];
$calltext = "Add New User";
$callroute = 'add';

$formvars = ['pagetitle', 'message', 'name', 'email', 'job', 'roles', 'id', 'route', 'override', 'button', 'priv'];
$uservars = ['manage', 'priv', 'client', 'users'];
$domainstr = "RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))";
$is_client_sql = "SELECT client.id AS employer, domain, email FROM client LEFT JOIN user ON $domainstr = client.domain WHERE user.email=:email";

$isContractor = function ($pdo, $email, $clientid = NULL) use ($is_client_sql) {
  $st = $pdo->prepare($is_client_sql);
  $st->bindValue(':email', $email);
  doPreparedQuery($st, 'Error querying client credentials');
  $row = $st->fetch(PDO::FETCH_ASSOC);
  return (isset($row['employer']) && is_null($clientid)) ? $row['employer'] : $clientid;
};

$clientflag = $_GET['clientflag'] ?? NULL;
$pwd = $_GET['pwd'] ?? NULL;
$agency = $_GET['agency'] ?? NULL;
$echange = $_GET['echange'] ?? NULL;
$lastuser = $_GET['lastuser'] ?? NULL;

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
    /*  //from filter list
    "SELECT domain FROM client WHERE domain=:domain"
    $sqlc = "SELECT employer.user_name, employer.user_id FROM (SELECT user.name AS user_name, user.id AS user_id, client.domain FROM user INNER JOIN client ON $domainstr=client.domain) AS employer WHERE employer.domain=:domain"; //
    */
    $st = doQuery($pdo, "SELECT name, domain, tel FROM client ORDER BY name", "Database error fetching clients");
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
      $client[$row['domain']] = $row['name'];
    }

    return [$users, $client];
  }
}

function isFreelancer($pdo, $id)
{
  $sql = "SELECT id, email FROM user WHERE client_id IS NULL AND id=:id";
  $st = $pdo->prepare($sql);
  $st->bindValue(':id',  $id);
  doPreparedQuery($st, 'Error fetching client.');
  $row = $st->fetch(PDO::FETCH_ASSOC);
  return $row['id'];
}

function hasDomain($key)
{
  include CONNECT;
  $st = $pdo->prepare("SELECT domain FROM client WHERE domain=:domain");
  $st->bindValue(":domain", $key);
  doPreparedQuery($st, "Unable to identify domain");
  return $st->fetch(PDO::FETCH_ASSOC);
}



function checkCurrentDetails($id, $p = '')
{
  include CONNECT;
  $row = isFreelancer($pdo, $id);
  if (!$row) {
    $domainstr = "RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))";
    $st = $pdo->prepare("SELECT id, name, email, $domainstr AS dom FROM user WHERE id =:id");
    $st->bindValue(":id", $id);
    doPreparedQuery($st, "Error fetching user details");
    $row = $st->fetch(PDO::FETCH_ASSOC);
  }
  return  $p ? $row[$p] : $row;
}

function canEdit($id, $postemail, $priv)
{
  $logemail = strtolower($_SESSION['email']);

  $row = checkCurrentDetails($id);
  $email = $row['email'] ?? '';

  $dbemail = NULL;
  if ($row) {
    $dbemail = strtolower($email);
  }
  if ($postemail) {
    $postemail = strtolower($postemail);
  } else {
    $postemail = $logemail;
  }

  return [$logemail === $dbemail, $postemail !== $logemail, $row['dom'] ?? '', isApproved($priv, 'admin')];
}

function filterUsers($sql, $key, $pagetitle, $error = '')
{
  //$key expected to be freelance id or domain
  $domainstr = "RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))";
  $return = "Return to users";
  $pagehead = "Manage Users";
  $users = [];
  $calltext = "Delete User";
  $selected = true;
  include CONNECT;
  $st = $pdo->prepare("SELECT domain FROM client WHERE domain=:domain");
  $st->bindValue(":domain", $key);
  doPreparedQuery($st, "Unable to identify domain");
  $row = $st->fetch(PDO::FETCH_ASSOC);
  /*some clients need full domain for identification, in which case the query is simplified to a straight match to a users email address which corresponds to the client domain.
  if (strrpos($key, "@")) {
    $domainstr = "user.email";
  }
    */
  if ($row) {
    // $selected = true;
    //a client is logged in
    $sqlc = "SELECT employer.user_name, employer.user_id FROM (SELECT user.name AS user_name, user.id AS user_id, client.domain FROM user INNER JOIN client ON $domainstr=client.domain) AS employer WHERE employer.domain=:domain"; //
    $st = $pdo->prepare($sqlc);
    $st->bindValue(":domain", $row['domain']);
    doPreparedQuery($st, "Database error fetching users.");
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
      header("Location: ./?nousers");
    }
    foreach ($rows as $row) {
      $users[$row['user_id']] = $row['user_name'];
    }
    $usercount = count($users);
    setExtent($usercount);
    if ($usercount === 1) {
      $key = $row['user_id'];
      $usercount = 1;
      $location = "./?edit=$key";
      if (!empty($error)) {
        $location .= "&error=$error";
      }
      header("Location: $location");
      exit;
    }
  } else {
    $callroute = "delete=$key";
    header("Location: ./?edit=$key");
    exit;
  }
  //load users
  return [$sql, $users, $selected, $return, $pagehead, $pagetitle];
}
function defaultQuery($key, $priv)
{
  if (preg_match("/client/i", $priv)) {
    return "SELECT id, name FROM user where id = $key ORDER BY name";
  }
  return "SELECT id, name FROM user ";
}

function whoAmI($email, $priv)
{
  $editor = $_SESSION['email'] === $email;
  $admin = isApproved($priv, 'admin');
  return [$editor, $admin];
}

function updateUserDomain($old, $new, $id = 0)
{
  if ($old && $new) {
    include CONNECT;
    $concat = replaceStrPos($new);
    //update email of employees IF the domain of client changes
    $sql = "UPDATE user SET email = $concat WHERE email LIKE '%$old'";
    //but restrict to a specific employee (eg leaving)
    if ($id) {
      $sql .= "  AND id=$id";
    }
    doQuery($pdo, $sql, '');
  }
}

//object and prop or; no prop object MUST be a domain
function isEmployer($o, $p = '')
{
  $domainstr = "RIGHT(user.email, LENGTH(user.email) - LOCATE('@', user.email))";
  $sql = "SELECT client.id AS employer, domain, email FROM client LEFT JOIN user ON $domainstr = client.domain";

  $id = null;
  if (!$p) {
    $sql = "SELECT client.id AS employer, domain, name FROM client WHERE client.domain = '$o'";
  }
  if ($p === 'email') {
    $sql .= "  WHERE user.email=:id";
    $id = $o[$p];
  }
  if ($p === 'id') {
    $sql .= "  WHERE user.id=:id";
    $id = $o[$p];
  }

  if ($p === 'employer') {
    $id = $o[$p];
    $sql = "SELECT client.id, domain FROM client WHERE client.id =:id";
  }

  return function ($pdo) use ($id, $sql) {
    $st = $pdo->prepare($sql);
    if (isset($id)) {
      $st->bindValue(':id',  $id);
    }
    doPreparedQuery($st, 'Error fetching client.');
    return $st->fetch(PDO::FETCH_NUM);
  };
}

function fetchAllRoles($pdo, $keys = [], $selectedRoles = [])
{
  //Build the list of all roles
  $st = doQuery($pdo, "SELECT id, description FROM role", 'Error fetching list of roles.');
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  if ($keys !== []) {
    $rows = reAssoc($rows, $keys, 'id', 'description', [], 0, 0);
  }

  foreach ($rows as $row) {
    $roles[] = array('id' => $row['id'], 'description' => $row['description'], 'selected' => in_array($row['id'], $selectedRoles));
  }

  return $roles;
}

function fetchSelectedRoles($pdo, $id, $keys = [])
{
  $st = $pdo->prepare("SELECT roleid FROM userrole WHERE userid=:id");
  $st->bindValue(":id", $id);
  doPreparedQuery($st, "<p>Error fetching list of assigned roles.</p>");
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $row) {
    $selectedRoles[] = $row['roleid'];
  }
  return fetchAllRoles($pdo, $keys, $selectedRoles);
}

function resetRoles($role, $roles, $id)
{
  if (isQualified($role)) {
    include CONNECT;
    foreach ($roles as $role) {
      $sql = "INSERT INTO userrole SET userid=:id, roleid=:rol";
      $st = $pdo->prepare($sql);
      $st->bindValue(":id", $id);
      $st->bindValue(":rol", $role);
      doPreparedQuery($st, '<p>Error assigning selected role to user.</p>');
    } //end foreach
    return $roles;
  }
}

function getCurrentRole($id)
{
  include CONNECT;
  $sql = "SELECT roleid FROM userrole WHERE userid=:id";
  $st = $pdo->prepare($sql);
  $st->bindValue(":id", $id);
  doPreparedQuery($st, 'Error obtaining role id.');
  return $st->fetch(PDO::FETCH_ASSOC)['roleid'];
}

function verifyRole($old, $new)
{
  return ($old === $new) ? false : true;
}

function deleteRole($id, $role)
{
  if (isQualified($role)) {
    include CONNECT;
    $role = getCurrentRole($id);
    $sql = "DELETE FROM userrole WHERE userid=:id";
    $st = $pdo->prepare($sql);
    $st->bindValue(":id", $id);
    doPreparedQuery($st, 'Error removing obsolete user role entries.');
  }
}
//maybe require password of client to delete??
function deleteAlready($id)
{
  include CONNECT;
  $st = $pdo->prepare("DELETE FROM user WHERE id =:id");
  $st->bindValue(':id', $id);
  doPreparedQuery($st, 'Error deleting user.');
  header('Location: . ');
  exit();
}

function updatePassword($pdo, $password, $id)
{
  $password = md5($password . 'uploads');
  $sql = "UPDATE user SET password =:pwd  WHERE id =:id";
  $st = $pdo->prepare($sql);
  $st->bindValue(':pwd', $password);
  $st->bindValue(':id', $id);
  return doPreparedQuery($st, 'Error setting user password.');
}

if (isset($_GET['domain'])) {
  updateUserDomain($_GET['domain'], $_GET['updated']);
}
if (!userIsLoggedIn()) {
  $pagetitle = "Log In";
  include TEMPLATE . 'login.html.php';
  exit();
}

$roleplay = userHasWhatRole();
$pagehead_role = $roleplay && !userHasWhatRole(true);

if (!$roleplay || $pagehead_role) {
  $e = 'Only Account Administrators may access this page!';
  $pagetitle = "Access Denied";
  //a manager role would be allowed to visit the landing page so redirect to there ../
  header("Location: ../?loginerror=$e");
  exit();
}
list($key, $priv) = $roleplay;
list($editor, $echange, $domain, $_agency) = canEdit($id, '', $priv);
$pagetitle = preg_match("/client/i", $priv) ? "Admin" : "Admin | Edit Users";
//exits
if (isset($_GET['add'])) {
  include CONNECT;
  $route = "Add";
  $action = 'addform';
  $button = 'Add User';
  $override = '';
  $crud = isApproved($priv, 'admin');
  $admin = isApproved($priv, 'ADMIN');
  $clientadmin = isApproved($priv, 'Client Admin');
  if (!$crud) {
    header("Location: ./?addno");
    exit();
  }
  $roles = fetchAllRoles($pdo, $roleorder);
  if ($admin) {
    $rows = doQuery($pdo, "SELECT * FROM client", "");
    foreach ($rows as $row) {
      $clientlist[$row['id']] = $row['name'];
    }
  }
  if ($clientadmin && !$admin) {
    unset($clientlist);
    $st = $pdo->prepare($is_client_sql);
    $st->bindValue(":email", $_SESSION['email']);
    doPreparedQuery($st, "Error fetching client details");
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $job = empty($row) ? NULL : $row['employer'];
    $roles = safeFilter($roles, function ($role) {
      return $role['id'] !== 'Admin';
    });
  }
  $pagehead = "New User";
  include 'form.html.php';
  exit();
} //////////////END OF ASSIGN

if (isset($_POST['action']) && $_POST['action'] === 'Add') {
  include CONNECT;
  $clientid = $_POST['employer'] ?? NULL;
  $clientadmin = preg_match("/admin/i", $priv) && preg_match("/client/i", $priv);
  $essentials = [$_POST['name'], $_POST['email'], $_POST['password']];
  $essentials = array_filter($essentials, function ($item) {
    return $item;
  });

  if (count($essentials) < 3) {
    header("Location: ./?addnotice");
    exit();
  }
  $sql = "INSERT INTO user (name, email, password, client_id) VALUES(:nom, :e,:pwd, :clientid)";
  $st = $pdo->prepare($sql);
  /*
  applies to admin users only as $_POST['employer'] is set by default by client
  if the email field corresponds to an existing client AND there is no selection in the assign to client drop down $_POST['employer'] then we can correct with the isContractor function
  if we have a clientid provided by $_POST['employer'] we must check that the supplied email DOMAIN matches
  Potentially there may be a case for having a contractor role but it would have to be deleted if the client was removed from the database and without a clientid this would not happen through the referential integrity enforced by the database. But an additional check could be made at this point, in the meantime the "contractor" would be free to leave the role
  */
  $st->bindValue(':nom', $_POST['name']);
  $st->bindValue(':e', $_POST['email']);
  $st->bindValue(':pwd', $_POST['password']);
  $st->bindValue(':clientid', intval($clientid));
  $res = doPreparedQuery($st, 'Error adding user');
  $aid = lastInsert($pdo);
  $contractor = $isContractor($pdo,  $_POST['email'], $clientid);
  if (isset($_POST['password']) && $_POST['password'] != '') {
    $res = updatePassword($pdo, $_POST['password'], $aid);
  }
  $roles = isset($_POST['roles']) ? $_POST['roles'] : [];

  if ($clientid || $contractor) {
    $st = doQuery($pdo, "SELECT domain FROM client WHERE id=$clientid", "Error retrieving clients from database!");
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $truedom = $row['domain'];
    $sql = "SELECT $domainstr AS dom FROM user WHERE client_id=:cid AND id=:aid";
    $st = $pdo->prepare($sql);
    $st->bindValue(':aid', $aid);
    $st->bindValue(':cid', intval($clientid));
    doPreparedQuery($st, 'Error fetching domain.');
    $row = $st->fetch(PDO::FETCH_ASSOC);
    updateUserDomain($row['dom'], $truedom, $aid);
    if ($contractor) { //required?
      doQuery($pdo, "UPDATE user INNER JOIN client ON $domainstr = client.domain SET client_id=$contractor WHERE $domainstr = client.domain", "Error updating client");
    }
  }
  resetRoles($priv, $roles, $aid);
  header('Location: .');
  exit();
} //end of addform

//exits
if (isset($_POST['confirm'])) {
  $location = " .";

  //dump($usercount);

  if ($_POST['confirm'] == 'Yes') {
    include CONNECT;
    $admin = ($priv == 'Admin');
    $id = $_POST['id'];
    $role = null;
    $roles = [];

    list($editor, $echange, $domain, $agency) = canEdit($id, '', $priv);
    $crud = ($agency || $editor);

    //editor or freelance
    if (!$domain) {
      if ($admin && $editor) {
        header("Location: ./?self"); //delegate deleting an admin to a peer
        exit();
      } else if ($crud) {
        deleteAlready($id);
        header("Location: .");
        exit();
      }
    }
    $sql = "SELECT user.id FROM user INNER JOIN client ON user.client_id = client.id WHERE client.domain='$domain'";
    $st = doQuery($pdo, $sql, 'Error fetching client.');
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $rolesql = "SELECT role.id AS role, userrole.userid AS id FROM role LEFT JOIN userrole ON role.id = userrole.roleid WHERE userrole.userid=:id";
    $st = $pdo->prepare($rolesql);

    if (!empty($rows)) {
      foreach ($rows as $r) {
        $st->bindValue(':id',  $r['id']);
        doPreparedQuery($st, 'Error fetching client.');
        $ro = $st->fetch(PDO::FETCH_ASSOC);
        $roles[$ro['id']] = $ro['role'];
      }
    }

    if (count($rows) === 1) {
      $location = $editor ? "Location: ./?lastclient" : "Location: ./?lastuser";
      header($location);
      exit();
    }
    $role = isset($roles[$id]) ? $roles[$id] : NULL;
    $danger = preg_match("/admin/i", $role);
    if ($danger) {
      $roles = safeFilter($roles, function ($role) {
        return preg_match("/admin/i", $role);
      });
    }
    $danger = $danger || count($roles) < 2;
    $denied = $crud ? false : ($role === 'Client');

    if (!$denied && !$danger) {
      deleteAlready($id);
    } else {
      $location .= "/?denied";
    }
  }
  header("Location: $location");
  exit();
} //confirm


if (isset($_GET['delete'])) {
  $id = $_GET['delete'];
  $title = "Prompt";
  $prompt = "Are you sure you want to delete this user?";
  $call = "confirm";
  $pos = "Yes";
  $neg = "No";
  $action = '';
  $formname = 'deleteuserform';
  $template = 'confirm.html.php';
  $crud = $editor || $_agency;
  if (!$crud) {
    header("Location: ./?denied");
  }
}

if (isset($_POST['action']) && $_POST['action'] === 'Edit') {
  include CONNECT;
  $action = "editform";
  $override = $_POST['override'];
  $id = $_POST['id'];
  $roles = $_POST['roles'] ?? [];
  $assoc = false;
  $revert = false;
  $email = null;
  $role = null;
  list($editor, $echange, $domain, $_agency) = canEdit($id, $_POST['email'] ?? '', $priv);
  if ($editor && $echange && !$override) {
    $relocation = "Location: ./?pwd=$id";
    header($relocation);
    exit();
  }

  $admin = $priv === 'Admin';
  $i = strpos($_POST['email'], '@');
  $edom = substr($_POST['email'], $i + 1);
  $isEmployer = isEmployer($edom);
  list($clientid, $domain) = $isEmployer($pdo);
  $freelancer = isFreelancer($pdo, $id);
  //$employerid only available from ADMIN; default to zero NOT NULL so it survives equality test with $clientid see $notice
  $employerid = empty($_POST['employer']) ? 0 : intval($_POST['employer']);
  $relocation = "Location: ./?clientflag=$id";
  //should user BE a freelancer

  if ($freelancer) {
    if (isset($clientid)) { //attempt by freelancer to join client; no priv
      $assoc = $admin ? true : false;
      header($relocation);
      exit();
    } else {
      $assoc = true;
    }
    $isEmployer = isEmployer($_POST, 'employer');
    list($clientid, $domain) = $isEmployer($pdo);
  } else {
    $isEmployer = isEmployer($_POST, 'id');
    list($clientid, $domain, $email) = $isEmployer($pdo);
    if (!$clientid) {
      //allow admin to = reinstate freelancer status
      if ($admin && !$employerid) {
        $domain = $edom;
      } else {
        $relocation = "Location: ./?clientflag=$id";
        header($relocation);
        exit();
      }
    }
  }

  if ($editor || $agency) {
    $sql = "UPDATE user SET name=:name, email=:email";
    $sql .= $assoc ? ", client_id=:cid" : ' ';
    $sql .= " WHERE id=:id";
    $st = $pdo->prepare($sql);

    if ($assoc) {
      $st->bindValue(":cid", $clientid);
    }
    $st->bindValue(":name", $_POST['name']);
    $st->bindValue(":email", $revert ? $email : $_POST['email']);
    $st->bindValue(":id", $id);
    doPreparedQuery($st, '<p>Error setting user details.</p>');
    //check EXISTING email not $_POST

    if (isset($_POST['password']) && $_POST['password'] != '') {
      if ($override) {
        $res = updatePassword($pdo, $_POST['password'], $id);
      } else {
        header("Location: ./?pwd=$id");
        exit();
      }
    }

    $role = getCurrentRole($id);
    deleteRole($id, $priv);
    resetRoles($priv, $roles, $id);
    $rolechange = verifyRole($role, getCurrentRole($id));
    //$clientid is allowed to be NULL (not any other empty) if a user wants to disassociate from a client
    $sql = "UPDATE user SET client_id=:cid WHERE id =:id";
    $st = $pdo->prepare($sql);
    $st->bindValue(":cid", $clientid);
    $st->bindValue(":id", $id);
    doPreparedQuery($st, 'Error setting client id');

    updateUserDomain($edom, $domain, $id);
    if ($editor) {
      if ($echange || ($rolechange && $editor)) {
        header("Location: ../?action=logout");
        exit();
      }
    }
  }
  header('Location: .');
  exit();
} ///END OF editform


//directly load form.html.php if only one user/client
if ((isset($_GET['edit'])) || $agency || $pwd || $clientflag) {

  include CONNECT;
  $class = '';
  $admin = ($priv === 'Admin');
  $clientadmin = preg_match("/admin/i", $priv) || preg_match("/client/i", $priv);
  $adminClient = preg_match('/admin/i', $priv) && preg_match('/client/i', $priv);
  $message = $_GET['error'] ?? '';
  $id = isset($_GET['edit']) ? $_GET['edit'] : ($pwd ? $pwd : NULL);
  $id = !empty($id) ? $id : $_POST['id'] ?? '';

  $calltext = "Delete User";
  $callroute = "delete=$id";

  $warning = 'You do not have sufficient privileges to edit this users details.';
  list($editor, $echange, $domain, $_agency) = canEdit($id, $_POST['email'] ?? '', $priv);
  //DON'T FORGET WE CAN ARRIVE HERE DIRECT FROM A LINK AND NOT FROM A REDIRECT FROM EDITING
  if (isset($_GET['error'])) {
    unset($calltext);
    unset($callroute);
  }
  if (!$agency) {
    if ($editor || $_agency) {
      $warning = '';
    } else {
      $message = $warning;
    }
  }

  $st = $pdo->prepare("SELECT id, name, email, $domainstr AS dom FROM user WHERE id =:id");
  $st->bindValue(":id", $id);
  doPreparedQuery($st, "Error fetching user details");
  $row = $st->fetch(PDO::FETCH_ASSOC);
  $editor = $_SESSION['email'] === $row['email'];

  if (!$message) { //either editor or admin
    $warning = 'Polite Notice: changing an email or password will automatically log you out.';
    $message = ($pwd && $editor) ? $warning : ''; //alert if yourself
    $message = $message ? $message : ($clientflag ? 'You do not have sufficient privileges to change the domain name. Please contact the database administrator.' : '');
    if ($message && ($message === $warning)) {
      $message .= ' You can proceed now that the form is in override mode but you will need to log in again with your updated details.';
    }
  }

  if ($clientflag) {
    $sql = "SELECT user.id, user.name, user.email FROM client LEFT JOIN user ON $domainstr = client.domain WHERE user.id=:dom";
    $st = $pdo->prepare($sql);
    $st->bindValue(":dom", $clientflag);
    doPreparedQuery($st, "Error fetching user details.");
    $row = $st->fetch(PDO::FETCH_ASSOC);
  }

  $route = "Edit";
  $pagehead = 'Edit User';
  $action = 'editform';
  $button = 'Update User';
  $roles = [];
  $clientlist = [];

  $id = $row['id'];
  $name = $row['name'];
  $email = $row['email'];
  $override = $pwd ? $pwd : NULL;
  $class = $override ? 'details override' : 'details';

  //prep roles...
  if (preg_match('/admin/i', $priv)) {
    $st = $pdo->prepare("SELECT roleid FROM userrole WHERE userid=:id");
    $st->bindValue(":id", $id);
    doPreparedQuery($st, "<p>Error fetching list of assigned roles.</p>");
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
      $selectedRoles[] = $row['roleid'];
    }
    $roles = fetchAllRoles($pdo, $roleorder, $selectedRoles);
  }

  if ($admin) {
    $st = doQuery($pdo, "SELECT id, name FROM client ORDER BY name", 'Error retrieving clients from database!');
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
      $clientlist[$row['id']] = $row['name'];
    }
    $st = $pdo->prepare("SELECT client_id FROM user WHERE id=:id");
    $st->bindValue(":id", $id);
    doPreparedQuery($st, "<p>Error retrieving client id from user!</p>");
    $row = $st->fetch(PDO::FETCH_ASSOC);
    $job = $row['client_id']; //selects client in drop down menu
  }

  if ($adminClient) {
    $roles = safeFilter($roles, function ($role) {
      return $role['id'] !== 'Admin';
    });
  }
  $action = "editform";
  include 'form.html.php';
  exit();
} //get_edit


//LANDING...
//$sql = defaultQuery($key, $priv);
//display users___________________________________________________________________
$sql = "SELECT user.id, user.name FROM user LEFT JOIN (SELECT user.name, client.domain FROM user INNER JOIN client ON $domainstr=client.domain) AS employer ON $domainstr=employer.domain WHERE employer.domain IS NULL"; //this overwrites above query to filter out users as employees
$sql = "SELECT user.id, user.name FROM user LEFT JOIN client ON user.client_id=client.id WHERE client.domain IS NULL"; //USING ID NOT DOMAIN
$admin = isApproved($priv, 'ADMIN');


if (isset($_POST['user'])) { //dropdown
  if ($_POST['user'] === '') {
    header("Location: ./?selectuser");
    exit();
  }
  list($sql, $users, $selected, $return, $pagehead, $pagetitle) = filterUsers($sql, $_POST['user'], $pagetitle);
}

//on landing try client; a single client will redirect to form.html.php, a multi team client will prepare variables for users.html.php
if ($users === []) {
  include CONNECT;
  $st = doQuery($pdo, "SELECT domain FROM client LEFT JOIN user ON $domainstr = client.domain WHERE user.id=$key", '');
  $row = $st->fetch(PDO::FETCH_ASSOC);
  $domain = $row['domain'] ?? NULL;
  //$error = query();

  if ($domain && !isset($prompt)) {
    list($sql, $users, $selected, $return, $pagehead, $pagetitle) = filterUsers($sql, $row['domain'], $pagetitle, $error);
  }
}

if ($users === []) {
  include CONNECT;
  if (!$admin) {
    $sql .= " AND user.id=$key";
  }
  $sql .= " ORDER BY name";
  $st = doQuery($pdo, $sql, '');
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $row) {
    $users[$row['id']] = $row['name'];
  }
}

//prepare list
if ($admin) {
  include CONNECT;
  $result = doQuery($pdo, "SELECT client.domain, client.name FROM client ORDER BY name", 'Database error fetching clients:');
  $rows = $result->fetchAll();
  foreach ($rows as $row) {
    $client[$row['domain']] = $row['name'];
  }
}

//include CONNECT;
//reOrderTable($pdo);
//reAssignClient($pdo);

$message = $message ? $message : $error;
$usercount = isApproved($priv, 'ADMIN') ? 2 : count($users); //2 ie more than 1
//setExtent is largely used for displaying conditional content, appropriate buttons etc..
setExtent($usercount);
if ($usercount === 1 && !isset($prompt)) {
  $calltext = "Delete User";
  $callroute = "delete=$key";
  $location = "./?edit=$key";
  if (!empty($error)) {
    unset($callroute);
    unset($calltext);
    $location .= "&error=$error";
  }
  header("Location: $location"); //GO DIRECT TO EDIT FORM
  exit();
} else {
  //clients of one in number can only end up here if a prompt is set and usercount is zero
  $pagehead = isApproved($priv, 'client') ? "Manage Team" : "Manage Users";
  include 'users.html.php';
}
