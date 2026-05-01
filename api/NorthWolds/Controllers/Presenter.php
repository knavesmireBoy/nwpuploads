<?php

namespace NorthWolds\Controllers;

use \Ninja\DatabaseTable;

class Presenter
{

    protected function presentList($role, $userId, $table)
    {
        $clients = [];
        $usr = [];
        $all = $table->findAll();
        if (isApproved($role, 'ADMIN')) {
            foreach ($all as $k => $row) {
                if (empty($row->client_id)) {
                    $usr[$k]['name'] =  $row->name;
                    $usr[$k]['id'] = $row->id;
                } else {
                    $u = $table->find('id', $row->id)[0];
                    $details = $u->getDetails();
                    if (!empty($details)) {
                        $clients[$k]['domain'] = $details['domain'];
                        $clients[$k]['name'] = $details['clientname'];
                    }
                }
            }
            array_multisort(array_column($usr, 'name'), SORT_ASC, $usr);
            array_multisort(array_column($clients, 'name'), SORT_ASC, $clients);
            $users = toKeyValue($usr, 'id', 'name');
            $client = toKeyValue($clients, 'domain', 'name');
            return [$users, $client];
        } else {
            $user = $table->find('id', $userId);
            $user = $user[0] ?? null;
            if (isset($user)) {
                $users = $user->getUserIds();
                if (isset($users[1])) {
                    foreach ($users as $k => $v) {
                        $u = $table->find('id', $v)[0];
                        $usr[$u->id] = $u->name;
                    }
                }
                return [$usr, []];
            }
        }
        return [[], []];
    }

}