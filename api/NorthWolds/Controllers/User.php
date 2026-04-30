<?php

namespace NorthWolds\Controllers;

use \Ninja\DatabaseTable;

class User
{
    public function __construct(private DatabaseTable $table, private DatabaseTable $clienttable, private string $home) {}
    private function getCustomVars($key, $data)
    {
    }

    private function displayer($priv, $customVars = [], $owner = [])
    {

        $error = query();
        $message = $error ?? '';
        $nwp_id = $_GET['edit'] ?? null;
        $nwproleplay = obtainUserRole();
        $pagehead_role = $nwproleplay && !obtainUserRole(true);
        $predicates = [partial('preg_match', '/^nwp/')];
        $redirects = ['pwd', 'domainflag', 'domainassoc', 'namechange'];

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
            'nwproleorder' => ['Browser', 'Manager', 'Client', 'Client Admin', 'Admin'],
            'owner' => $owner
        ];

        $vars = array_merge($defaultVars, $customVars);

        return [
            'template' => 'clients.html.php',
            'title' => 'Edit Clients',
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