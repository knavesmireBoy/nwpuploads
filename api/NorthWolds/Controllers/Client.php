<?php

namespace NorthWolds\Controllers;

use \Ninja\DatabaseTable;

class Client
{
    public function __construct(private DatabaseTable $table, private DatabaseTable $usertable, private string $home) {}

    private function displayer($priv, $owner = [], $customVars = [])
    {
        //list($users, $clients) = $this->presentList($priv, $userId);
        $rows = $this->table->findAll(null, 0, 0, \PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $clients[] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'domain' => $row['domain']
            );
        }

        $defaultVars = [
            'priv' => $priv,
            'pagehead' => 'Manage Clients',
            'action' => '/client/select/',
            'callroute' => '/client/add/',
            'calltext' => 'Add Client',
            'clients' => $clients,
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
        $user = $this->usertable->find('email', $_SESSION['username'])[0];
        $details = $user->getDetails();
        $priv = $details['role'];
        $customVars = $this->getCustomVars($key, $vars);

        $owner = [];

        if (isset($vars['id'])) {
            $client = $this->table->find('id', $vars['id'])[0];
            $owner['id'] = $vars['id'];
            $owner['name'] = $client->name;
            $owner['domain'] = $client->domain;
        }


        return $this->displayer($priv, $owner, $customVars);
    }

    private function getCustomVars($key, $data)
    {
        // if($key === 'delete') dump($data);
        $ret = [];
        $id = $data['id'] ?? '';
        $lib = [
            'choose' => ['id' => $id, 'template' => 'clientform.html.php', 'pagehead' => 'Edit Client', 'calltext' => 'Delete Client', 'callroute' => "/client/delete/$id", 'action' => '/client/edit/',  'button' => 'Update Client', 'selected' => $id, 'name' => $data['name'] ?? '', 'tel' => $data['tel'] ?? '', 'domain' => $data['domain'] ?? ''],

            'add' => ['template' => 'clientform.html.php', 'pagetitle' => 'Admin | Client', 'pagehead' => 'New Client', 'calltext' => 'Add Client', 'action' => '/client/edit/', 'button' => 'Add Client'],

            'delete' => ['id' => $id, 'template' => 'prompt.html.php', 'title' => 'Prompt', 'prompt' => "Are you sure you want to delete this client?", 'call' => 'confirm', 'pos' => 'Yes', 'neg' => 'No', 'action' => '/client/confirm/'],

            'confirm' => ['id' => $id],

        ];

        if ($key && isset($lib[$key])) {
            $ret = $lib[$key];
        }
        return $ret;
    }
    public function select()
    {
        if (empty($_POST['client'])) {
            reLocate($this->home);
        }
        
        $client = $this->table->find('id', $_POST['client'], null, 0, 0, \PDO::FETCH_ASSOC)[0];
        $data = ['id' => $client['id'], 'name' => $client['name'], 'domain' => $client['domain'], 'tel' => $client['tel']];
        

        return $this->load('choose', $data);
    }

    public function add()
    {
        return $this->load('add');
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
        $this->table->delete('id', $_POST['id']);
        reLocate($this->home);
    }

    public function editSubmit()
    {
        $edit = false;
        if ($_POST['id']) {
            $res = $this->table->find('id', $_POST['id'], null, 0, 0, \PDO::FETCH_ASSOC);
            $values = $res[0] ? $res[0] : [];
            $edit = true;
        } else {
            $values = $_POST['data'];
        }
        if ($edit) {
            foreach ($_POST['data'] as $k => $v) {
                if ($v && isset($values[$k])) {
                    $values[$k] = $v;
                }
            }
        }
        $this->table->save($values, empty($_POST['id']));
        reLocate($this->home);
    }
}
