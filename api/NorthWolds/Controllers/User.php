<?php

namespace NorthWolds\Controllers;

use \Ninja\DatabaseTable;

class User
{
    public function __construct(private DatabaseTable $table, private DatabaseTable $clienttable, private string $home) {}

    private function getCustomVars($key, $data)
    {
        //if($key === 'confirm') dump($data);
        $ret = [];
        $id = $data['id'] ?? '';

        $lib = [
            'add' => ['pagehead' => 'New User', 'template' => 'userform.html.php', 'route' => 'Add', 'action' => '/user/edit/', 'button' => 'Add User', 'legend' => null, 'override' => null, 'email' => '']
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

    private function presentList($role, $userId, $table)
    {
        $clients = [];
        $usr = [];
        $all = $table->findAll();
        if (isApproved($role, 'ADMIN')) {
            foreach ($all as $k => $row) {
                if (empty($row->client_id)) {
                    $usr[$k]['name'] =  $row->name;
                    $usr[$k]['id'] = $row->id;
                } else {
                    $u = $table->find('id', $row->id)[0];
                    $details = $u->getDetails();
                    if (!empty($details)) {
                        $clients[$k]['domain'] = $details['domain'];
                        $clients[$k]['name'] = $details['clientname'];
                    }
                }
            }
            array_multisort(array_column($usr, 'name'), SORT_ASC, $usr);
            array_multisort(array_column($clients, 'name'), SORT_ASC, $clients);
            $users = toKeyValue($usr, 'id', 'name');
            $client = toKeyValue($clients, 'domain', 'name');
            return [$users, $client];
        } else {
            $user = $table->find('id', $userId);
            $user = $user[0] ?? null;
            if (isset($user)) {
                $users = $user->getUserIds();
                if (isset($users[1])) {
                    foreach ($users as $k => $v) {
                        $u = $table->find('id', $v)[0];
                        $usr[$u->id] = $u->name;
                    }
                }
                return [$usr, []];
            }
        }
        return [[], []];
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
}
