<?php

namespace NorthWolds\Controllers;

use \Ninja\DatabaseTable;

class User extends Presenter
{
    public function __construct(protected DatabaseTable $table, private DatabaseTable $clienttable, private string $home)
    {
        parent::__construct($table);
    }

    private function hasChanged($db, $post, $prop)
    {
        return $post[$prop] && $db[$prop] !== $post[$prop];
    }

    private function foo()
    {
        $title = "Prompt";
        $prompt = "Changing your email will log you out of the current session. Proceed?";
        $call = "confirm";
        $pos = "Yes";
        $neg = "No";
        $action = '/user/change/';
        $template = 'confirm.html.php';
    }

    protected function getCustomVars($key, $data)
    {
        //if($key === 'confirm') dump($data);
        $ret = [];
        $id = $data['id'] ?? '';
        $users = $key === 'selected' ? $data : [];

        $lib = [
            'edit' => ['calltext' => 'Delete User', 'callroute' => "/user/delete/$id"],
            'delete' => ['id' => $id, 'template' => 'prompt.html.php', 'title' => 'Prompt', 'prompt' => "Are you sure you want to delete this user?", 'call' => 'confirm', 'pos' => 'Yes', 'neg' => 'No', 'action' => '/user/confirm/'],
            'confirm' => ['id' => $id],
            'selected' => ['pagehead' => 'Select User', 'selected' => true, 'clients' => [], 'users' => $users],
            'change' => ['id' => $id, 'template' => 'prompt.html.php', 'title' => 'Prompt', 'prompt' => "Changing these details will require you to log in again. Proceed?", 'call' => 'confirm', 'pos' => 'Yes', 'neg' => 'No', 'action' => '/user/change/', 'override' => 'change'],
        ];

        if ($key && isset($lib[$key])) {
            $ret = $lib[$key];
        }
        return $ret;
    }

    protected function getPrivilege($prop = '')
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
        $details = $this->getPrivilege();
        $customVars = $this->getCustomVars($key, $vars);
        //  if ($key === 'selected') dump($customVars);
        $owner = []; //prompt.html.php expects this from Uploader Controller
        return $this->displayer($details, $customVars, $owner);
    }

    public function add()
    {
        $priv = $this->getPrivilege('role');
        $admin = isApproved($priv, 'ADMIN');

        if (!$admin) {
            reLocate($this->home);
        }
        return $this->edit(0, [
            'action' => 'user/add/',
            'pagehead' => 'Add User',
            'button' => 'Add User',
            'calltext' => null,
            'callroute' =>  null
        ]);
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

    public function edit($id, $args = [])
    {
        $details = $this->getPrivilege();
        $admin = isApproved($details['role'], 'ADMIN');
        $user = $id ? $this->table->find('id', $id)[0] : $this->table->getEntity();
        $id = $user->id ?? null;
        list($_, $clients) = $this->presentList($details['role'], $id, $this->table, 'client_id');
        $roles = $user->getRoles();

        $vars = [
            'admin' => $admin,
            'priv' => $details['role'],
            'editor' => $id == $details['id'],
            'class' => '',
            'legend' => '',
            'override' => '',
            'pagehead' => 'Edit User',
            'action' => '/user/edit/',
            'id' => $id,
            'name' => $user->name ?? '',
            'email' => $user->email ?? '',
            'employer' => $user->client_id ?? '',
            'button' => 'Edit User',
            'calltext' => 'Delete User',
            'callroute' => "/user/delete/$id",
            'clientlist' => $clients,
            'roles' => $roles
        ];
        return [
            'template' => 'userform.html.php',
            'title' => 'Edit User',
            'variables' => [...$vars, ...$args]
        ];
    }

    public function addSubmit()
    {
        $data = $_POST['data'];
        $client_id = $_POST['employer'] ?? $_POST['employed'];
        $required = array_filter($data, function ($item) {
            return $item;
        });
        $role = $_POST['roles'][0] ?? 'Browser';
        if (count($required) < 3) {
            reLocate($this->home . "/");
        }
        $userId = $this->getLastInsertId($this->table->save([...$data, 'client_id' => nullify($client_id)], true));
        $user = $this->table->find('id', $userId)[0];
        $user->updatePassword($data['password']);
        //role must be set BEFORE "updateUserDomain" no user can navigate the site without an assigned role
        $user->setRole($role);
        $user->updateUserDomain(nullify($_POST['employer']), get_object_vars($user), $userId);
    }

    public function editSubmit()
    {
        $id = nullify($_POST['id']);
        $data = $_POST['data'];
        $editor = intval($id) === $this->getPrivilege('id');
        $values = [];
        $required = array_filter($data, function ($item) {
            return $item;
        });
        $role = $_POST['roles'][0] ?? 'Browser';
        $user = $this->table->find('id', $id)[0];
        $values = get_object_vars($user);
        //exclude password from update unless requested...
        $data = [...$values, ...$required];
        $change = $this->hasChanged($values, $required, 'password');
        if ($change && $editor) {
            return $this->load('change', ['id' => $id]);
        }
        unset($values['password']);
        $user = $this->table->save($data);

        if (isset($data['password']) &&  $data['password'] !== '') {
            $user->updatePassword($data['password']);
        }
        $user->setRole($role); //UPDATE role here
        $user->updateUserDomain(nullify($_POST['employer']), $values);
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

    public function changeSubmit()
    {
        if (isset($_POST['confirm']) && $_POST['confirm'] === 'Yes') {
            return $this->edit($_POST['id']);
        }
        reLocate($this->home);
    }

    public function destroy($id)
    {
        $this->table->delete('id', $id);
        reLocate($this->home);
    }
}
