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


    private function queryClient($str = '')
    {
        //NOTE id AS employer AND domain in that order as expected by list($employer, $domain)
        $dom = fromStrPos(DBSYSTEM);
        $where = null;
        $sql = "SELECT client.id AS employer, domain, usr.id, usr.email, usr.name FROM client LEFT JOIN usr ON $dom = client.domain";

        $options = ['email' => " WHERE usr.email=:aux", 'id' => " WHERE usr.id=:aux", 'employer' => " WHERE client.id=:aux"];
        if (is_string($str)) {
            $where = $options[strtolower($str)] ?? null;
        }
        if ($where) {
            return $sql . $where;
        } else if (is_array($str)) { //empty array to signify fetchAll
            if (!empty($str)) {
                $str = $str[0];
                return "SELECT usr.id, usr.name, usr.email, client.domain, roleid AS role FROM usr INNER JOIN client ON usr.client_id = client.id INNER JOIN userrole ON userrole.userid = usr.id WHERE client.domain = $str ORDER BY name";
            }
            return "SELECT usr.id, usr.name, usr.email, client.domain, roleid AS role FROM usr INNER JOIN client ON usr.client_id = client.id INNER JOIN userrole ON userrole.userid = usr.id WHERE client.domain=:aux ORDER BY name";
        } else if (is_null($str)) {
            return "SELECT usr.id, usr.name FROM usr LEFT JOIN client ON usr.client_id=client.id WHERE client.domain IS NULL";
        } else {
            return "SELECT client.id AS employer, domain FROM client WHERE client.domain LIKE '$str%'";
        }
    }

    private function presentClientList($role, $prop = 'id', $flag = 'admin')
    {
        $users = [];
        $client = [];
        if (isApproved($role, $flag)) {
            include CONNECT;
            $st = doQuery($pdo, $this->queryClient(null), "Error retrieving details");
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
