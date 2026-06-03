<?php

namespace NorthWolds\Controllers;

use \Ninja\DatabaseTable;

class User extends Presenter
{

    private $punter;

    public function __construct(protected DatabaseTable $table, private DatabaseTable $clienttable, private string $home)
    {
        parent::__construct($table);
    }

    protected function getSubUser(\NorthWolds\Entity\User $user)
    {
        $details = $user->getDetails();
        $entity = $this->getEntity($details);
        $this->table->setEntity($entity);
        return $this->fetch('table', 'id', $user->id);
    }

    private function destroy($id)
    {
        $this->table->delete('id', $id);
        reLocate($this->home);
    }

    private function query($key, $arg = '')
    {
        $lib = [
            'nousers' => "Unable to find any users",
            "addnotice" => "Please fill required fields",
            "selectuser" => "Please select a user for editing",
            "domainflag" => "Cannot assign this user to a new client",

            "denied" => "You do not have the privileges to delete this user",
            "deniedbyclient" => "There must be at least one administrator role, please assign another user before removing your credentials from the database",
            "access" => "You do not have the privileges to add a user",

            "self" => "Only a peer can perform this deletion.",
            "freelancer" => "Cannot assign this domain.",
            'addno' => 'You do not have the required privilges to add a user.',
            'editno' => 'You do not have the required privilges to edit this user details.',
            'read' => 'You may view but not edit this user details.',

            'undef' => "Missing property: $arg",
            '_admin' => "You cannot delete an administrator.",
            "lasteditor" => "There must be at least one administrator role, please assign another user before removing credentials from the database.",
            "lastadminrole" => "There must be at least one administrator role, please promote another user to the admin role before demoting your status.",
            '_lastadminrole' => "To demote the admin status of this user you must promote another user to the admin role.",

            'last' => "Only the database administrator can delete this user.",
            "_denied" => "Cannot assign this user to a client.",
            '_last' => "To remove this final user, please delete the client instead.",

            'domain' => 'Only the database administrator can change the domain of an email address.',
            '_domain' => 'Set the drop down menu to empty when changing the domain. Change the domain of the client to update the domain for all members.',
            '_cadmin' => 'Cannot change the domain of a user with an admin role',
            'traitor' => 'That domain is not available.',
            '_traitor' => 'To assign to another client use the drop down menu.',
            '_move' => 'Cannot move a user with an admin role',
            '_impostor' => 'That domain is in use, use the client list drop down to assign a user.'
        ];

        return $lib[$key] ?? '';
    }

    protected function getCustomVars($key, $data)
    {
        $ret = [];
        $id = $data['id'] ?? '';
        $users = $key === 'selected' ? $data : [];
        $prompt = ['pagehead' => 'Edit User', 'id' => $id, 'template' => 'prompt.html.php', 'title' => 'Prompt', 'call' => 'confirm', 'pos' => 'Yes', 'neg' => 'No'];

        $lib = [
            'edit' => ['calltext' => 'Delete User', 'callroute' => "/user/delete/$id"],
            'delete' => [...$prompt, 'prompt' => "Are you sure you want to delete this user?", 'action' => '/user/confirm/'],
            'confirm' => ['id' => $id],
            'selected' => ['pagehead' => 'Select User', 'selected' => true, 'clients' => [], 'users' => $users],
            'change' => ['pagehead' => 'Edit User', 'id' => $id, 'template' => 'prompt.html.php', 'title' => 'Prompt', 'prompt' => "Changing these details will require you to log in again. Proceed?", 'call' => 'confirm', 'pos' => 'Yes', 'neg' => 'No', 'editor' => $id, 'action' => '/user/change/'],
            'leave' => [...$prompt, 'editor' => $id, 'action' => '/user/change/', 'prompt' => "Are you sure you want to disassociate this user from the client?", 'call' => 'confirm','cookie' => 'client_id'],
            //note pos set but not neg and call not required; the logic being if just pos the dialog need not include radio buttons but simply supply a button for further editing
            'success' => ['pagehead' => 'Edit User', 'template' => 'prompt.html.php', 'action' => "/user/editbridge/$id", 'prompt' => "Details succesfully updated", 'pos' => 'Yes', 'submit' => 'edit again']
        ];

        if ($key && isset($lib[$key])) {
            $ret = $lib[$key];
        }
        return $ret;
    }

    private function hasChanged($db, $post, $mandatory, $optionals)
    {
        $ret = [];
        $opt = [];
        foreach ($mandatory as $prop) {
            if (isset($post[$prop]) && $db[$prop] !== $post[$prop]) {
                $ret[] = $prop;
            }
        }
        foreach ($optionals as $prop) {
            if (isset($post[$prop]) && $db[$prop] !== $post[$prop]) {
                $opt[] = $prop;
            }
        }
        return [$ret, $opt];
    }

    protected function getEntity($details)
    {
        $role = str_replace(' ', '', $details['role']);
        if (preg_match('/client/i', $role)) {
            if (isset($details['colleagues'])) {
                $role = $details['colleagues'] ? $role : 'Solo';
                $role = $details['administrator'] ? 'ClientAdminSolo' : $role;
                $role = ($role === 'Client') ? 'Employee' : $role;
            } else {
                $role = preg_match('/admin/', $role) ? 'Admin' : 'Freelancer';
            }
        }
        return "NorthWolds\\Entity\\$role";
    }

    protected function getPrivilege($prop = '')
    {
        if (!isset($_SESSION['username'])) {
            reLocate(REG);
        }
        $user = $this->table->find('email', $_SESSION['username']);
        if (!isset($user[0])) {
            reLocate(REG);
        }
        $details = $user[0]->getDetails();
        return $prop ? $details[$prop] : $details;
    }

    protected function getAccess($key)
    {

        $msg = 'This page is restricted to Account Administrators';
        $lib = ['load' => $msg, 'add' => $msg, 'edit' => $msg, 'delete' => $msg];
        return $lib[$key] ?? '';
    }

    public function message($action = '', $i = 0)
    {
        if ($action) {
            return [
                'template' => 'accessdenied.html.php',
                'variables' => [
                    'error' => $this->getAccess($action),
                    'route' => '/upload/load',
                    'submitted' => false
                ]
            ];
        } else {
            reLocate(REG);
        }
    }

    protected function displayer($details, $customVars = [], $owner = [], $error = '')
    {
        //  $error = query();
        $message = $error ?? '';
        // $pagehead_role = $nwproleplay && !obtainUserRole(true);
        $predicates = [partial('preg_match', '/^nwp/')];
        // $clients = isApproved($priv, 'ADMIN') ? $this->presentClientList($priv, 'domain') : [];
        $cadmin = isApproved($_SESSION['role'], 'admin');
        $details = $this->getPrivilege();

        $user = $this->fetch('table', 'id', $details['id']);
        $user = $this->getSubUser($user);
        $user->setSelf(!empty($customVars));

        $payload = $user->loadPayload(isset($customVars['selected']));
        list($users, $clients) = $this->presentList($details['role'], $details['id'], $this->table);
        $defaultVars = [
            'prompt' => null,
            'users' => $users,
            'clients' => $clients,
            'usercount' => 0,
            'denied' => false,
            'nwpagency' => null,
            'pagehead' => 'Edit Details',
            'pageid' => 'admin_user',
            'nwproleplay' => 'Admin',
            'nwp_id' => null,
            'pagehead_role' => 'Admin',
            'error' => $error,
            'nwproleorder' => ['Browser', 'Manager', 'Client', 'Client Admin', 'Admin'],
            'owner' => $owner,
            'redirects' => ['pwd', 'domainflag', 'domainassoc', 'namechange'],
            'predicates' => [partial('preg_match', '/^nwp/')],
            'pages' => 1
        ];

        $vars = array_merge($defaultVars, $customVars, $payload);
        return [
            'template' => 'users.html.php',
            'title' => 'Edit Users',
            'variables' => $vars
        ];
    }

    public function loadbridge($key, $id, ...$args)
    {
        return $this->load($key, ['id' => $id, ...$args]);
    }

    public function load(string $key = '', array $vars = [])
    {
        $details = $this->getPrivilege();
        $customVars = $this->getCustomVars($key, $vars);

        $owner = []; //prompt.html.php expects this from Uploader Controller
        unset($vars['id']);
        //the occasional error may require ONE argument which is not an id
        $error = $this->query($key, ...$vars);
        $user = $this->fetch('table', 'email', $_SESSION['username']);
        $user = $this->getSubUser($user);

        if ($user->preEdit(empty($customVars))) {
            $args = $error ? ['message' => $error] : [];
            $id = $details['id'];
            return $this->edit($id, [...$customVars, ...$args]);
        }
        return $this->displayer($details, $customVars, $owner, $error);
    }

    public function add()
    {
        $details = $this->getPrivilege();
        $punter = $this->fetch('table', 'id', $details['id']);
        $roles = $punter->getRoles($punter->id);
        return $this->edit(0, [
            'action' => "user/add/",
            'pagehead' => 'Add User',
            'button' => 'Add User',
            'employer' => $details['client_id'] ?? 0,
            'roles' => $roles
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
                reLocate("/user/edit/$id");
            }
        } else {
            $client = $this->clienttable->find('domain', $_POST['user']);
            $client = $client[0] ?? null;
            if (!$client) {
                return $this->load('selected', []);
            }
            $users = $this->table->find('client_id', $client->id);
            $usrs = [];
            $i = count($users);
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

    public function editbabe($id, $str)
    {
        parse_str($str, $args);

        dump($_COOKIE);
        return $this->edit($id, $args);
    }

    public function editbridge($id, ...$args)
    {
        return $this->edit($id, [...$args]);
    }

    public function edit($id, $args = [])
    {
        $details = $this->getPrivilege();
        $punter = $this->fetch('table', 'id', $details['id']);
        $punter = $this->getSubUser($punter);
        $editor = ($id == $details['id']);
        $user = $this->fetch('table', 'id', $id);

        //$id maybe 0 if an add request bypasses load; should this happen?
        if ($user) {
            $user = $this->getSubUser($user);
            $user->setSelf($editor);
        }

        $member = $editor ? $user : $punter;
        $member->setSelf($editor);
        $payload = $member->editPayload($id);

        list($_, $clients) = $member->presentList($id, 'client_id');
        $roles = $member->getRoles($user->id);

        $id = $user->id ?? null;
        $vars = [
            'button' => 'Edit User',
            'pagehead' => 'Edit User',
            'action' => '/user/edit/',
            'id' => $id,
            'name' => $_COOKIE['name'] ?? $user->name ?? '',
            'email' => $_COOKIE['email'] ?? $user->email ?? '',
            'password' => $_COOKIE['password'] ?? '',
            'employer' => $_COOKIE['client_id'] ?? $user->client_id ?? '',
            'editor' => $editor,
            'clientlist' => $clients,
            'roles' => $roles,
        ];

        $this->setCookie($_COOKIE, ['name', 'email', 'password', 'client_id'], false);
        return [
            'template' => 'userform.html.php',
            'title' => 'Edit User',
            'variables' => [...$vars, ...$args, ...$payload]
        ];
    }

    public function addSubmit()
    {
        $data = $_POST['data'];
        $client_id = $_POST['employer'] ?? $_POST['employed'];
        $key = isApproved($_SESSION['role'], 'ADMIN') ? '_domain' : 'domain';
        $required = array_filter($data, function ($item) {
            return $item;
        });
        $role = $_POST['roles'][0] ?? 'Client';

        if (count($required) < 3) {
            reLocate($this->home . "/");
        }
        $userId = $this->getLastInsertId($this->table->save([...$data, 'client_id' => nullify($client_id)], true));
        $user = $this->fetch('table', 'id', $userId);

        $user->updatePassword($data['password']);
        $updateDomain = $user->updateDomain($key, $data['id'], empty($_POST['override']));
        /*role must be set BEFORE "updateDomain"
        no user can navigate the site without an assigned role*/
        $user->setRole($role);
        $data = $updateDomain(nullify($client_id), get_object_vars($user), $userId);
        $this->table->save($data);
        reLocate($this->home);
    }

    public function editSubmit()
    {
        $setcookie = doSetCookie(true);
        $unsetcookie = doSetCookie(false);

        $admin = $_SESSION['role'] === 'Admin';
        $id = nullify($_POST['id']);
        $key = '';
        $data = $_POST['data'];
        $user = $this->fetch('table', 'id', $id);
        //list of roles (radio buttons) may not be present
        $role = isset($_POST['roles']) ? $_POST['roles'][0] : $user->getDetails('roleid');
        $clientID = $_POST['employer'] ?? $_POST['employed'] ?? null;

        $user = $this->getSubUser($user);
        $editor = $id == $this->getPrivilege('id');
        $user->setSelf($editor);

        $updateDomain = $user->updateDomain($admin ? '_domain' : 'domain', $id, empty($_POST['override']));

        $record = get_object_vars($user);
        $required = array_filter($data, fn($item) => $item);

        $dom = $updateDomain(nullify($clientID), $data);

        //will exit here if domain doesn't validate
        $data = [...$record, ...$required, ...$dom];
        //exclude password from update unless requested...
        list($change, $optional) = $this->hasChanged($record, $required, ['email', 'password'], ['name']);

        if ($editor && $change !== [] && empty($_POST['override'])) {
            $this->setCookie($data, [...$change, ...$optional], true);
            //reLocate("/user/loadbridge/change/$id");
            return $this->load('change', ['id' => $id]);
        }
        $user->updatePassword($required['password'] ?? '');
        unset($data['password']);
        $user = $this->table->save($data);
        $user =  $this->getSubUser($user);
        $user->setSelf($editor);
        $key = $user->setRole($role, $admin ? '_last' : 'lastadmin'); //UPDATE role here; it may trigger an error message
        if (!$key) {
            $updated = get_object_vars($user);
            $result = array_diff_assoc($record, $updated);
            if (!empty($result)) {
                $key = $user->postEdit();
            }
            $relocate = $key ? '/user/loadbridge/' . strtolower($key) . "/$id" : null;
        }
        $relocate = $relocate ?? '/user/load/' . strtolower($key);
        reLocate($relocate);
    }

    public function delete($id)
    {
        $msg = '';
        $details = $this->getPrivilege();
        $user = $this->fetch('table', 'id', $id);
        $user = $this->getSubUser($user);
        $user->setSelf($id == $details['id']);
        $msg = $user->delete($id);
        if ($msg) {
            return reLocate($this->home . $msg);
        }
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

   
            if(isset($_POST['cookie'])){
                parse_str($_POST['cookie'], $cookie);
                $this->setCookie($_COOKIE, array_keys($cookie), false);
                $id = $_POST['id'];
                $str = urlencode('You may now proceed with your edits!');
                $cookie = "class=details%20override&override=override&legend=$str";
                reLocate("/user/editbabe/$id/$cookie");
            }
            else {
                return $this->edit($_POST['id'], ['class' => 'details override', 'override' => 'override', 'legend' => 'You may now proceed with your edits']);
            }
        }
        else {
            $this->setCookie($_COOKIE, ['name', 'email', 'password', 'client_id'], false);
        }
        reLocate($this->home);
    }
}
