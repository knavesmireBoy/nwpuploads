<?php

namespace NorthWolds\Controllers;

use \Ninja\DatabaseTable;

class User extends Presenter
{
    public function __construct(protected DatabaseTable $table, private DatabaseTable $clienttable, private string $home)
    {
        parent::__construct($table);
    }

    protected function getCustomVars($key, $data)
    {
        //if($key === 'confirm') dump($data);
        $ret = [];
        $id = $data['id'] ?? '';
        $users = $key === 'selected' ? $data : [];

        $lib = [
            /*
            'add' => ['pagehead' => 'New User', 'template' => 'userform.html.php', 'route' => 'Add', 'action' => '/user/edit/', 'button' => 'Add User', 'legend' => null, 'override' => null, 'email' => ''],
            'edit' => [
                'pagehead' => 'Edit User',
                'template' => 'userform.html.php',
                'action' => '/user/edit/',
                'id' => $id,
                'button' => 'Edit User',
                'route' => 'Edit',
                'name' => $data['name'] ?? '',
                'email' => $data['email'] ?? '',
                'override' => $data['override'] ?? '',
                'legend' => '',
                'selected' => true,
                'roles' => []
            ],
            */
            'edit' => ['calltext' => 'Delete User', 'callroute' => "/user/delete/$id"],
            'delete' => ['id' => $id, 'template' => 'prompt.html.php', 'title' => 'Prompt', 'prompt' => "Are you sure you want to delete this user?", 'call' => 'confirm', 'pos' => 'Yes', 'neg' => 'No', 'action' => '/user/confirm/'],
            'confirm' => ['id' => $id],
            'selected' => ['pagehead' => 'Select User', 'selected' => true, 'clients' => [], 'users' => $users]
        ];

        if ($key && isset($lib[$key])) {
            $ret = $lib[$key];
        }
        return $ret;
    }

    protected function grabPriv($prop = '')
    {
        if (!isset($_SESSION['username'])) {
            reLocate(REG);
        }
        $user = $this->table->find('email', $_SESSION['username'])[0];
        $details = $user->getDetails();
        return $prop ? $details[$prop] : $details;
    }

    protected function displayer($details, $customVars = [], $owner = [])
    {
        //  $error = query();
        $message = $error ?? '';
        // $pagehead_role = $nwproleplay && !obtainUserRole(true);
        $predicates = [partial('preg_match', '/^nwp/')];
        // $clients = isApproved($priv, 'ADMIN') ? $this->presentClientList($priv, 'domain') : [];

        list($users, $clients) = $this->presentList($details['role'], $details['id'], $this->table);
        $admin = isApproved($details['role'], 'ADMIN');
        $defaultVars = [
            'admin' => $admin,
            'priv' => $details['role'],
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
        $details = $this->grabPriv();
        $customVars = $this->getCustomVars($key, $vars);
        //  if ($key === 'selected') dump($customVars);
        $owner = []; //prompt.html.php expects this from Uploader Controller
        return $this->displayer($details, $customVars, $owner);
    }

    public function add()
    {
        $priv = $this->grabPriv('role');
        $admin = isApproved($priv, 'ADMIN');

        if (!$admin) {
            reLocate($this->home);
        }
        return $this->edit();
    }

    public function selectSubmit()
    {
        $key = '';
        if (isset($_POST['user']) && is_numeric($_POST['user'])) {
            $user = $this->table->find('id', $_POST['user']);
            $user = $user[0] ?? null;
            if ($user) {
                $id = $user->id;
                setExtent(1);
                reLocate("/user/edit/$id");
            }
        } else {
            $client = $this->clienttable->find('domain', $_POST['user']);
            $users = $this->table->find('client_id', $client[0]->id);
            $usrs = [];
            $i = count($users);
            setExtent($i);
            if ($i > 1) {
                foreach ($users as $usr) {
                    $usrs[$usr->id] = $usr->name;
                }
                return $this->load('selected', $usrs);
            } else {
                $id = $users[0]->id;
                reLocate("/user/edit/$id");
            }
        }
    }

    public function edit($id = null)
    {
        $details = $this->grabPriv();
        $admin = isApproved($details['role'], 'ADMIN');
        $user = $id ? $this->table->find('id', $id)[0] : $this->table->getEntity();
        $id = $user->id ?? null;
        list($_, $clients) = $this->presentList($details['role'], $id, $this->table, 'client_id');
        $roles = $user->getRoles();
        return [
            'template' => 'userform.html.php',
            'title' => 'Edit User',
            'variables' => [
                'admin' => $admin,
                'priv' => $details['role'],
                'editor' => $id == $details['id'],
                'legend' => '',
                'override' => '',
                'pagehead' => $id ? 'Edit User' : 'Add User',
                'action' => '/user/edit/',
                'id' => $id,
                'name' => $user->name ?? '',
                'email' => $user->email ?? '',
                'employer' => $user->client_id ?? '',
                'button' => $id ? 'Edit User' : 'Add User',
                'calltext' => $id ? 'Delete User' : null,
                'callroute' => $id ? "/user/delete/$id" : null,
                'clientlist' => $clients,
                'roles' => $roles
            ]
        ];
    }

    public function editSubmit()
    {
        $id = nullify($_POST['id']);
        $data = $_POST['data'];
        $client_id = $_POST['employer'] ?? $_POST['employed'];
        $keys = ['id', 'name', 'email', 'client_id'];
        $values = [];
        $required = array_filter($data, function ($item) {
            return $item;
        });
        $role = $_POST['roles'][0] ?? 'Browser';
        if ($id) {
            $user = $this->table->find('id', $id)[0];

            dump(toObject($user, true));
            $values = $this->table->find('id', $id, null, 0, 0, \PDO::FETCH_ASSOC)[0];
            $data = [...$values, ...$required];
            $user = $this->table->save($data);
            if (isset($data['password']) &&  $data['password'] !== '') {
                $user->updatePassword($data['password']);
            }
            $user->setRole($role);
            $user->updateUserDomain(nullify($_POST['employer']), $values);
        } else {
            if (count($required) < 3) {
                reLocate($this->home . "/");
            }
            $userId = $this->getLastInsertId($this->table->save([...$data, 'client_id' => nullify($client_id)], true));

            $user = $this->table->find('id', $userId)[0];
            $values = $this->table->find('id', $userId, null, 0, 0, \PDO::FETCH_ASSOC)[0];
            $user->setRole($role);
            $user->updateUserDomain(nullify($_POST['employer']), $values, $userId);
        }
    }

    public function delete($id)
    {
        return $this->load('delete', ['id' => $id]);
    }

    public function confirm()
    {
        if (isset($_POST['confirm']) && $_POST['confirm'] === 'Yes') {
            return $this->destroy($_POST['id']);
        }
        reLocate($this->home);
    }

    public function destroy($id)
    {
        $this->table->delete('id', $id);
        reLocate($this->home);
    }
}
