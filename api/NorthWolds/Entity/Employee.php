<?php

namespace NorthWolds\Entity;

class Employee extends ClientAdmin
{
    public function __construct(...$args)
    {
        parent::__construct(...$args);
    }

    protected function resetClient($cid)
    {
        $ret = $this->fetch('clienttable', 'id', $cid);
        return [$cid, $ret->domain];
    }

    public function presentList($userId)
    {
        return [[], []];
    }

    public function getRoles(int $userid)
    {
        return [];
    }

    public function edit($flag = true)
    {
        return $flag;
    }
    /*
    protected function validateDom($cid, $record, $ename, $edom, $insertId = 0)
    {
        $postdata['email'] = "$ename@$edom";
        $postdata['client_id'] = $cid;
        return $postdata;
    }
        */

    protected function validateDom($cid, $record, $ename, $edom, $insertId = 0)
    {
        list($name, $dom, $com) = $this->parseEmail($record['email']);
        $postdata['email'] = "$ename@$edom";
        $postdata['client_id'] = $cid;
        if ($edom === "$dom.$com" || !$cid) {
            return $postdata;
        }
        return false;
    }

    public function editPayload($id = '')
    {
        return [
            'retour' => '_return2uploads.html.php'
        ];
    }

    public function updateDomain($key, $uid, $override)
    {
        return function (?int $cid, array $postdata, int $id = 0) use ($key, $uid, $override) {
            list($name, $dom, $com) = $this->parseEmail($postdata['email']);
            $relocate = '';
            $dbrecord = $this->fetch('TABLE', 'id', $this->id);
            if (isset($dbrecord['email'])) { //existing
                $newdata = $this->validateDom($cid, $dbrecord, $name, "$dom.$com");
                if (!$newdata) {
                    reLocate("/user/load/$key");
                }
                $postdata = [...$postdata, ...$newdata];
                //can only be admin moving an employee; admin users cannot be moved
                if ($cid && $cid != $dbrecord['client_id']) {
                    $data = $this->fetch('clienttable', 'id', $cid);
                    $domain = $data->domain;
                    $postdata['email'] = "$name.$domain";
                    if ($override) {
                        $relocate = "/user/loadbridge/leave/$uid";
                    }
                } else { //admin releasing an employee OR updating name 
                    $clients = $this->clienttable->findAll();
                    //allow a user to change the name part of the email address; so filter out current domain IF NOT admin
                    $f = negate(curry2('equals')("$dom.$com"));
                    $f = $cid ? $f : 'identity';
                    $filter = curry2('array_filter')($f);

                    $checkDomains = composer(partial('in_array', "$dom.$com"), $filter, partial('array_values'), partial('array_map', curry2('getter')(0)), partial('array_map', 'parseEmail'), partial('array_column', $clients));

                    //allow a user to change the name part of the email address
                    if ($checkDomains('domain')) {
                        reLocate("/user/load/_traitor");
                    }
                    if (!$cid && $override) {//empty $cd courtesy of admin
                        $relocate = "/user/loadbridge/leave/$uid";
                    }
                }
                if ($relocate) {
                    $this->setCookie($postdata, ['name', 'email', 'client_id'], true);
                    reLocate($relocate);
                }
                return $postdata;
            } else { //new
                return $this->setClientEmail($cid, $name, $postdata);
            }
        };
    }
}
