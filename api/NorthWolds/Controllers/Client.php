<?php

namespace NorthWolds\Controllers;

use \Ninja\DatabaseTable;

class Client
{
    public function __construct(private DatabaseTable $table, private DatabaseTable $usertable) {}

    private function displayer($priv, $customVars = [])
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
            'clients' => $clients
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
        $user = $this->usertable->find('email', $_SESSION['username'])[0];
        $details = $user->getDetails();
        $priv = $details['role'];
        $customVars = $this->getCustomVars($key, $vars);
        return $this->displayer($priv, $customVars);
    }

    private function getCustomVars($key, $data)
    {
        //if($key === 'confirm') dump($data);
        $ret = [];
        $id = $data['id'] ?? '';

        $lib = [

            'choose' => ['id' => $id, 'pagehead' => 'Edit Client', 'action' => '/client/edit/', 'route' => 'Edited', 'calltext' => 'Delete Client', 'callroute' => "delete=$id", 'button' => 'Update Client', 'selected' => $id, 'template' => 'clientform.html.php', 'name' => $data['name'] ?? '', 'tel' => $data['tel'] ?? '', 'domain' => $data['domain'] ?? ''],

            'upload' => ['template' => 'upload.html.php', 'home' => '/uploader/load/'],

            'sort' => ['fhead' => $data['fhead'] ?? '', 'uhead' => $data['uhead'] ?? '', 'thead' => $data['thead'] ?? ''],

            'delete' => ['id' => $data['id'] ?? '', 'template' => 'prompt.html.php', 'title' => 'Prompt', 'prompt' => "Are you sure you want to delete this file?", 'call' => 'confirm', 'pos' => 'Yes', 'neg' => 'No', 'action' => '/uploader/confirm/'],

            'confirm' => ['id' => $data['id'] ?? '', 'template' => 'prompt.html.php', 'title' => 'Prompt', 'prompt' => "Select the extent of deletions", 'delete' => 'proceed',  'action' => '/uploader/destroy/'],

            'update' => ['id' => $data['id'] ?? '', 'button' =>  $data['button'] ?? '', 'all_users' => $data['users'] ?? [], 'colleagues' => $data['colleagues'] ?? [], 'group' => $data['group'] ?? '', 'answer' => $data['answer'] ?? '', 'action' => '/uploader/update/', 'template' => 'update.html.php', 'title' => 'Update', 'filename' => $data['filename'] ?? '', 'description' => $data['description'] ?? '', 'home' => '/uploader/load/']
        ];

        if ($key && isset($lib[$key])) {
            $ret = $lib[$key];
        }
        return $ret;
    }
    public function select()
    {
        $client = $this->table->find('id', $_POST['client'], null, 0, 0, \PDO::FETCH_ASSOC)[0];
        $data = ['id' => $client['id'], 'name' => $client['name'], 'domain' => $client['domain'], 'tel' => $client['tel']];
        return $this->load('choose', $data);
    }

    public function editSubmit()
    {

        $data = ['id', 'name', 'domain', 'tel'];
        $ret = [];

        $values = $this->table->find('id', $_POST['id'], null, 0, 0, \PDO::FETCH_ASSOC)[0];
      
        foreach ($_POST as $k => $v){
            if(isset($values[$k])){
                $values[$k] = $v;
            }
        }

        dump($values);
        $ret['name'] = $_POST['name'];
        $ret['domain'] = $_POST['domain'];
        $ret['tel'] = $_POST['tel'];
     //   $this->table->save($ret);
    }
}
