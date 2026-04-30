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


    private function presentClientList($role, $prop = 'id', $flag = 'admin')
    {
        $users = [];
        $client = [];
        if (isApproved($role, $flag)) {
            include CONNECT;
            $st = doQuery($pdo, queryClient(null), "Error retrieving details");
            $rows = $st->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $users[$row['id']] = $row['name'];
            }
            $st = doQuery($pdo, "SELECT client.id, client.name, client.domain, client.tel FROM client ORDER BY name", "Database error fetching clients");
            $rows = $st->fetchAll(\PDO::FETCH_ASSOC);

            $st = $pdo->prepare("SELECT usr.id, usr.name FROM client INNER JOIN usr ON usr.client_id=client.id WHERE client.id=:id");
            foreach ($rows as $row) {
                $st->bindValue(":id", $row['id']);
                doPreparedQuery($st, "Database error fetching user");
                //filters out clients that have no users
                if ($st->fetch(\PDO::FETCH_ASSOC)) {
                    $client[$row[$prop]] = $row['name'];
                }
            }
            return $client;
        }
    }

    private function displayer($priv, $customVars = [], $owner = [])
    {

        //  $error = query();
        $message = $error ?? '';
        // $pagehead_role = $nwproleplay && !obtainUserRole(true);
        $predicates = [partial('preg_match', '/^nwp/')];
        $clients = isApproved($priv, 'ADMIN') ? $this->presentClientList($priv, 'domain') : [];

        $defaultVars = [
            'prompt' => null,
            'users' => [],
            'clients' => $clients,
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
