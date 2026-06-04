<?php

namespace NorthWolds\Entity;

class Employee extends User
{
    protected function setClientEmail($cid, $name, $data)
    {
        $client = $this->fetch('clienttable', 'id', $cid);
        $domain = $client->domain;
        $data['email'] = "$name@$domain";
        return $data;
    }

    public function postEdit()
    {
        return $this->self ? 'success' : '';
    }

    public function preEdit($flag = true)
    {
        return $flag;
    }

    //validate activity on the email field
    protected function validateDom($cid, $record, $ename, $edom, $insertId = 0)
    {
        list($name, $dom, $com) = $this->parseEmail($record['email']);
        $postdata['email'] = "$ename@$edom";
        $postdata['client_id'] = $cid;
        /*
        admin can change the domain provided there is no selection in the drop down menu
        so therefore no $cid ($client->id) allows a employee to become a freelancer
        */
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
            $cookiearg = true;
            $dbrecord = $this->fetch('TABLE', 'id', $this->id);
            $client = $this->fetch('clienttable', 'id', $cid);
            $clientdata = [];
            if (isset($dbrecord['email'])) { //existing
                /*
                $override is normal state ($_POST['override'] is empty) otherwise cookies have loadded new data in to the form and validateDom will fail as $postdata and $dbrecord won't tally; as this can only happen with an admin user in control we let it go
                */
                $newdata = $override ? $this->validateDom($cid, $dbrecord, $name, "$dom.$com") : $postdata;
                if (!$newdata) {
                    reLocate("/user/load/$key");
                }
                if (!$override && $client) {
                    $clientdata = $client->validateDomain($postdata['email'], 'client_id');
                    if (!$clientdata['client_id']) {
                        $relocate = "/user/load/_sync";
                        $cookiearg = false;
                    }
                }
                $postdata = [...$postdata, ...$newdata, ...$clientdata];

                //can only be admin moving an employee; admin users cannot be moved
                if ($cid && $cid != $dbrecord['client_id'] && $override) {
                    $data = $this->fetch('clienttable', 'id', $cid);
                    $domain = $data->domain;
                    $postdata['email'] = "$name@$domain";
                    $relocate = "/user/loadbridge/move/$uid/client_id=$cid";
                } else { //admin releasing an employee OR updating name 
                    $relocate = !$cid && $override ? "/user/loadbridge/leave/$uid/client_id=$cid" : '';
                    $clients = $this->clienttable->findAll();
                    //allow a user to change the name part of the email address; so filter out current domain IF NOT admin
                    $f = negate(curry2('equals')("$dom.$com"));
                    $f = $cid ? $f : 'identity';
                    $filter = curry2('array_filter')($f);

                    $checkDomains = composer(partial('in_array', "$dom.$com"), $filter, partial('array_values'), partial('array_map', curry2('getter')(0)), partial('array_map', 'parseEmail'), partial('array_column', $clients));

                    //allow a user to change the name part of the email address
                    if ($checkDomains('domain')) {
                        $cookiearg = false;
                        reLocate("/user/load/_traitor");
                    }
                }
                if ($relocate) {
                    $data = $cookiearg ? $postdata : $_COOKIE;
                    $this->setCookie($data, ['name', 'email', 'client_id'], $cookiearg);
                    reLocate($relocate);
                }
                return $cid ? $postdata : [...$postdata, ['client_id' => null]];
            } else { //new
                return $this->setClientEmail($cid, $name, $postdata);
            }
        };
    }
}
