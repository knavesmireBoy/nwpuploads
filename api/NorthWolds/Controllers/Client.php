<?php

namespace NorthWolds\Controllers;

use \Ninja\DatabaseTable;

class Client
{
    public function __construct(private DatabaseTable $table, private DatabaseTable $usertable) {}

    private function displayer($priv)
    {
        //list($users, $clients) = $this->presentList($priv, $userId);
        $rows = $this->table->findAll(null, 0, 0, \PDO::FETCH_ASSOC);
        dump($rows);
        foreach ($rows as $row) {
            $clients[] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'domain' => $row['domain']
            );
        }

        $defaultVars = [];
        /*
        $vars = array_merge($defaultVars, $customVars);
        if ($vars['searchtext']) {
            $vars['searchform'] = true;
        }
            */
        return [
            'template' => 'clients.html.php',
            'title' => 'Edit Clients',
            'variables' => [
                'priv' => $priv,
                'clients' => $clients
            ]
        ];
    }

    public function load(string $key = '', array $vars = [])
    {
        $user = $this->usertable->find('email', $_SESSION['username'])[0];

        var_dump($user);
        $details = $user->getDetails();
        $priv = $details['role'];
        return $this->displayer($priv);
    }
}
