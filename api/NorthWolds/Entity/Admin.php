<?php

namespace NorthWolds\Entity;

class Admin extends User
{

    public function __construct(...$args)
    {
        parent::__construct(...$args);
    }

    /*
    protected function validateRole($role)
    {
}

*/
    public function delete($id)
    {
        return '_admin';
    }

    public function editPayload($id = '')
    {
        if (!$this->self) {
            return [
                'calltext' => 'Delete User',
                'callroute' => "/user/delete/$id",
                'retour' => '_return2list.html.php'
            ];
        } else {
            return [
                'retour' => '_return2list.html.php'
            ];
        }
    }


    public function loadPayload($selected = null)
    {
        $base =  [
            'callroute' => '/user/add/',
            'calltext' => 'Add New User'
        ];
        if (!$selected) {
            return [
                ...$base,
                'retour' => '_return2uploads.html.php',
                'optgroup' => 'clients'
            ];
        } else {
            return [...$base, 'retour' => '_return2list.html.php'];
        }
    }

    public function getRoles(int $userid)
    {
        if (!$this->self) {
            $roleid = $this->getRole($userid);
            $user = $this->fetch('table', 'id', $userid);
            $roles = $this->roles;
            $admin = $roleid === 'Admin';

            if (!$user->client_id) {
                $j = array_search('Client Admin', $roles);
                $roles = array_slice($roles, 0, $j);
                $roles = $admin ? [...$roles, 'Admin'] : $roles;

                //discrepancy 'Client Admin' role must belong to a Client
                if ($roleid === 'Client Admin') {
                    $this->userroletable->delete('id', $userid);
                    $this->userroletable->save(['userid' => $userid, 'roleid' => 'Client']);
                }
            } else {
                $roles = array_slice($roles, 0, count($roles) - 1);
            }
            return $this->fetchAllRoles($roles, [$roleid]);
        }
        return [];
    }


    public function presentList($userId, $prop = 'domain')
    {
        if ($this->self) {
            return [[], []];
        }
        $clients = [];
        $usr = [];
        $all = $this->table->findAll();
        foreach ($all as $k => $row) {
            if (empty($row->client_id)) {
                $usr[$k]['name'] =  $row->name;
                $usr[$k]['id'] = $row->id;
            } else {
                $u = $this->table->find('id', $row->id)[0];
                $details = $u->getDetails();
                if (!empty($details)) {
                    $clients[$k][$prop] = $details[$prop];
                    $clients[$k]['name'] = $details['clientname'];
                }
            }
        }
        array_multisort(array_column($usr, 'name'), SORT_ASC, $usr);
        array_multisort(array_column($clients, 'name'), SORT_ASC, $clients);
        $users = toKeyValue($usr, 'id', 'name');
        $client = toKeyValue($clients, $prop, 'name');
        return [$users, $client];
    }
}
