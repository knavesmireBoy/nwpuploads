<?php

namespace NorthWolds\Entity;

class ClientAdmin extends User
{
    //return false to present list of users for logged in client
    public function edit($flag = true)
    {
        return false;
    }

    protected function resetClient($cid)
    {
        $ret = $this->fetch('clienttable', 'id', $this->client_id);
        return [$ret->id, $ret->domain];
    }

    public function updateDomain($key)
    {
        return function (?int $cid, array $postdata, int $insertID = 0) use ($key) {
            list($_name, $_dom, $_com) = $this->parseEmail($postdata['email']);
            $dbrecord = $this->fetch('TABLE', 'id', $this->id);
            if (isset($dbrecord['email'])) { //existing
                list($name, $dom, $com) = $this->parseEmail($dbrecord['email']);
                $key = "$_dom.$_com" !== "$dom.$com" ? $key : '';

                if ($cid && $key) {
                    reLocate("/user/load/$key");
                }
                //can only be admin moving an employee; admin users cannot be moved
                if ($cid && $cid != $dbrecord['client_id']) {
                    list($cid, $domain) = $this->resetClient($cid, "$dom.$com");
                    $postdata['email'] = "$_name@$domain";
                    $postdata['client_id'] = $cid;
                    if($domain === "$dom.$com"){
                        reLocate("/user/load/_move");
                    }
                    else {
                        if(empty($_POST['override'])){
                            $id = $_POST['id'];
                            $data = [...$postdata, ['cid' => $cid]];
                            $this->setCookie($data, ['name', 'email', 'cid'], true);
                            reLocate("/user/loadbridge/leave/$id");
                        }
                    }
                } else { //admin releasing an employee OR updating name 
                    $clients = $this->clienttable->findAll();
                    //$f = negate(curry2('equals')($dom));
                    //curry2('array_filter')($f)
                    $checkDomains = composer(partial('in_array', $_dom), partial('array_values'), partial('array_map', curry2('getter')(0)), partial('array_map', 'parseEmail'), partial('array_column', $clients));

                    if($checkDomains('domain')){
                        reLocate("/user/load/_traitor");
                    }

                    if(!$cid && empty($_POST['override'])){
                        $id = $_POST['id'];
                        $this->setCookie($_COOKIE, ['name', 'email'], true);
                        reLocate("/user/loadbridge/leave/$id");
                    }

                    $postdata['email'] = $cid ? "$_name@$dom.$com" : "$_name@$_dom.$_com";
                    $postdata['client_id'] = $cid;
                }
                return $postdata;
            } else { //new
                $client = $this->fetch('clienttable', 'id', $cid);
                $domain = $client->domain;
                $postdata['email'] = "$_name@$domain";
                return $postdata;
            }
        };
    }

    public function setRole(string $role)
    {
        //if $action is true insert otherwise update
        $action = empty($this->userroletable->find('userid', $this->id));
        //A) if validation fails return a key for query eg 'last' header(Location: /user/load/last)
        $role = $this->validateRole($role);

        if (in_array($role, $this->roles)) {
            $this->userroletable->save(['userid' => $this->id, 'roleid' => $role], $action);
            $this->roleid = $role;
            //B if validation succeeds set to empty string
            $role = '';
        }
        return $role;
    }

    public function getUserIds($roles = null)
    {
        $users = $this->table->find('client_id', $this->client_id);
        $res = array_map(fn($o) => $o->id, $users);
        if (is_bool($roles)) {
            $ret = $this->getAllRoles($res);
            return $roles ? $ret : $this->getAdminRoles($ret);
        }
        return $res;
    }

    public function editPayload($id = '')
    {
        return [
            'calltext' => 'Delete User',
            'callroute' => "/user/delete/$id",
            'retour' => '_return2list.html.php'
        ];
    }

    public function loadPayload($id = '')
    {
        return [
            'callroute' => '/user/add/',
            'calltext' => 'Add New User',
            'selected' => true, //!!foregoes the drop down menu required by Admin
            'retour' => '_return2uploads.html.php'
        ];
    }

    public function getRoles(int $userid)
    {
        $f = composer(negate(curry2('equals')('Admin')), curry2('getter')('id'));
        $roleid = $this->getRole($userid);
        $roles = $this->fetchAllRoles($this->roles, [$roleid]);
        return safeFilter($roles, $f);
    }

    public function presentList($userId)
    {
        $user = $this->table->find('id', $userId);
        $user = $user[0] ?? null;
        if (isset($user)) {
            $users = $user->getUserIds();
            if (isset($users[1])) {
                foreach ($users as $k => $v) {
                    $u = $this->table->find('id', $v)[0];
                    $usr[$u->id] = $u->name;
                }
            }
            return [$usr, []];
        }
    }
}
