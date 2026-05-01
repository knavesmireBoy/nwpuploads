<?php

namespace NorthWolds\Controllers;

use \Ninja\DatabaseTable;

class User extends Presenter
{
    public function __construct(private DatabaseTable $table, private DatabaseTable $clienttable, private string $home) {}

    private function getCustomVars($key, $data)
    {
        //if($key === 'confirm') dump($data);
        $ret = [];
        $id = $data['id'] ?? '';

        $lib = [
            'add' => ['pagehead' => 'New User', 'template' => 'userform.html.php', 'route' => 'Add', 'action' => '/user/edit/', 'button' => 'Add User', 'legend' => null, 'override' => null, 'email' => ''],
            'edit' => ['pagehead' => 'Edit User', 'template' => 'userform.html.php', 'action' => 'user/edit/', 'id' => $id, 'button' => 'Edit User', 'route' => 'Edit', 'name' => $data['name'] ?? '', 'email' => $data['email'] ?? '', 'override' => $data['override'] ?? '', 'employer' => $data['employer'] ?? '', 'legend' => '']
        ];

        if ($key && isset($lib[$key])) {
            $ret = $lib[$key];
        }
        return $ret;
    }

    private function grabPriv()
    {
        if (!isset($_SESSION['username'])) {
            reLocate(REG);
        }
        $user = $this->table->find('email', $_SESSION['username'])[0];
        $details = $user->getDetails();
        return $details['role'];
    }

    private function displayer($priv, $customVars = [], $owner = [])
    {

        //  $error = query();
        $message = $error ?? '';
        // $pagehead_role = $nwproleplay && !obtainUserRole(true);
        $predicates = [partial('preg_match', '/^nwp/')];
        // $clients = isApproved($priv, 'ADMIN') ? $this->presentClientList($priv, 'domain') : [];
        list($users, $clients) = $this->presentList($priv, null, $this->table);
        $admin = isApproved($priv, 'ADMIN');

        $defaultVars = [
            'prompt' => null,
            'users' => $users,
            'clients' => $clients,
            'optgroup' => $admin ? 'clients' : '',
            'usercount' => 0,
            'denied' => false,
            'usercount' => 0,
            'selected' => null,
            'nwpagency' => null,
            'pagehead' => 'Edit Details',
            'pageid' => 'admin_user',
            'callroute' => '/user/add/',
            'calltext' => 'Add New User',
            'nwproleplay' => 'Admin',
            'nwp_id' => null,
            'pagehead_role' => 'Admin',
            'error' => '',
            'message' => '',
            'nwproleorder' => ['Browser', 'Manager', 'Client', 'Client Admin', 'Admin'],
            'owner' => $owner,
            'redirects' => ['pwd', 'domainflag', 'domainassoc', 'namechange'],
            'predicates' => [partial('preg_match', '/^nwp/')],
            'admin' => $admin,
            'pages' => 1
        ];

        $vars = array_merge($defaultVars, $customVars);

        return [
            'template' => 'users.html.php',
            'title' => 'Edit Users',
            'variables' => $vars
        ];
    }

    public function load(string $key = '', array $vars = [])
    {
        $priv = $this->grabPriv();
        $customVars = $this->getCustomVars($key, $vars);
        $owner = []; //prompt.html.php expects this from Uploader Controller
        return $this->displayer($priv, $customVars, $owner);
    }

    public function add()
    {
        $priv = $this->grabPriv();
        $admin = isApproved($priv, 'ADMIN');

        if (!$admin) {
            //  header("Location: ./?addno");
            exit();
        }
        /*
        $roles = fetchAllRoles($pdo, $nwproleorder);
        if ($nwpadmin) {
            $clientlist = presentClientList($priv);
        }

        if (isApproved($priv, 'Client Admin') && !$nwpadmin) {
            unset($clientlist);
            $st = $pdo->prepare(queryClient('email'));
            $st->bindValue(":aux", $_SESSION['email']);
            doPreparedQuery($st, "Error fetching client details");
            $row = $st->fetch(PDO::FETCH_ASSOC);
            $employer = nullify($row['employer']);
            $email = $row['email'];
            $email = preg_replace("/[^@]+(@.+)/", "$1", $email);
            $roles = safeFilter($roles, $nwpRolesCallback);
        }


        $admin = $nwpadmin;
        include 'userform.html.php';
        exit();
        */
        return $this->load('add');
    }

    public function selectSubmit()
    {
        $key = '';
        dump(is_numeric($_POST['user']));
        if (isset($_POST['user']) && is_numeric($_POST['user'])) {
            $user = $this->table->find('id', $_POST['user']);
            $user = $user[0] ?? null;
            dump($user);
            if ($user) {
                $key = 'edit';
                $data = ['name' => $user->name, 'email' => $user->email, 'employer' => false, 'override' => ''];
                return $this->load($key, $data);
            }
        }
        reLocate($this->home);
    }
}
