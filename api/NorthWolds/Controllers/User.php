<?php

namespace NorthWolds\Controllers;

use \Ninja\DatabaseTable;

class User
{
    public function __construct(private DatabaseTable $table, private DatabaseTable $clienttable, private string $home) {}
    private function getCustomVars($key, $data)
    {
        return [];
    }

    private function displayer($priv, $customVars = [], $owner = [])
    {

      //  $error = query();
        $message = $error ?? '';
       // $pagehead_role = $nwproleplay && !obtainUserRole(true);
        $predicates = [partial('preg_match', '/^nwp/')];

        $defaultVars = [
            'prompt' => null,
            'users' => [],
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
            'admin' => isApproved($priv, 'ADMIN')
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
        if (!isset($_SESSION['username'])) {
            reLocate(REG);
        }
        $user = $this->table->find('email', $_SESSION['username'])[0];
        $details = $user->getDetails();
        $priv = $details['role'];
        $customVars = $this->getCustomVars($key, $vars);
        $owner = []; //prompt.html.php expects this from Uploader Controller
        return $this->displayer($priv, $customVars, $owner);
    }
}