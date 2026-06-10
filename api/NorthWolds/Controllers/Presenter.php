<?php

namespace NorthWolds\Controllers;

use \Ninja\DatabaseTable;

class Presenter
{

    public static int $success = 1;

    public function __construct(protected DatabaseTable $table)
    {
    }

    protected function presentList(string $role, mixed $userId, DatabaseTable $table, $prop = 'domain')
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
                        $clients[$k][$prop] = $details[$prop];
                        $clients[$k]['name'] = $details['clientname'];
                    }
                }
            }
            array_multisort(array_column($usr, 'name'), SORT_ASC, $usr);
            array_multisort(array_column($clients, 'name'), SORT_ASC, $clients);
            $users = toKeyValue($usr, 'id', 'name');
            $users = byLastName($users);
            $client = toKeyValue($clients, $prop, 'name');
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

    protected function idFromDomain(\Ninja\DatabaseTable $table, string $domain, int $permission, mixed $index = false)
    {
        $user = $table->getEntity();
        $client = $user->fromDomain($domain);

        $usrs = $client ? $table->find('client_id', $client->id, 'id') : [];
        if ($permission) {
            $users = safeFilter($usrs, fn($usr) => $usr->checkPermission($permission));
        }
        $users = empty($users) ? $usrs : $users;
        return is_int($index) ? $users[$index]->id : $users;
    }

    public function getRoute($role) {}

    protected function setCookie(array $data, array $mandatory, mixed $flag)
    {
        $setcookie = doSetCookie($flag);
        foreach ($mandatory as $prop) {
            $arg = isset($data[$prop]) && $flag ? $data[$prop] : '';
            $setcookie($prop, $arg);
        }
    }

    protected function getLastInsertId($id)
    {
        if (!is_int($id)) {
            $all = array_map(fn($o) => $o->id, $this->table->findAll());
            return max($all);
        }
        return $id;
    }

    protected function fetch($t, $prop, $val, ...$rest)
    {
        $ret = [];
        if ($val) { //safeguard against missing values
            if (strtoupper($t) === $t) {
                $t = strtolower($t);
                $ret = $this->{$t}->find($prop, $val, null, 0, 0, \PDO::FETCH_ASSOC);
            } else {
                $ret = $this->{$t}->find($prop, $val, ...$rest);
            }
        }
        return empty($ret) ? null : $ret[0];
    }
}
