<?php

namespace NorthWolds\Entity;

class Employee extends User
{


    public function postEdit()
    {
        return $this->self ? 'success' : '';
    }

    public function preEdit($flag = true)
    {
        return $flag;
    }

    //validate activity on the email field
    protected function validateDom($cid, $record, $postdata, $insertId = 0)
    {
        list($name, $dom, $com) = $this->parseEmail($record['email']);
        list($ename, $edom, $ecom) = $this->parseEmail($postdata['email']);

        $postdata = $this->setClientEmail($cid, $postdata, $record);

        /*
        admin can change the domain provided there is no selection in the drop down menu
        so therefore no $cid ($client->id) allows a employee to become a freelancer
        */
        if ("$edom.$ecom" === "$dom.$com" || !$cid) {
            $postdata['client_id'] = $cid;
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

    protected function traitorCheck($cid, $record, $postdata)
    {
        list($name, $dom, $com) = $this->parseEmail($record['email']);
        $f = negate(curry2('equals')("$dom.$com"));
        list($name, $dom, $com) = $this->parseEmail($postdata['email']);
        $f = $cid ? $f : 'identity';
        $filter = curry2('array_filter')($f);
        $clients = $this->clienttable->findAll();
        $traitorCheck = composer(partial('in_array', "$dom.$com"), $filter, partial('array_values'), partial('array_map', curry2('getter')(0)), partial('array_map', 'parseEmail'), partial('array_column', $clients));
        return $traitorCheck('domain');
    }

    public function updateDomain($key, $uid, $default)
    {
        $relocate = null;
        $cookiearg = true;
        $dbrecord = $this->fetch('TABLE', 'id', $this->id);
        if ($default) {
            return function (?int $cid, array $postdata, int $id = 0) use ($uid, $relocate, $cookiearg, $dbrecord) {

                if (isset($dbrecord['email'])) { //existing
                  //  $postdata = $this->setClientEmail($cid, $postdata, $dbrecord);
                    //can only be admin moving an employee; admin users cannot be moved
                    if ($cid && $cid != $dbrecord['client_id']) {
                        $this->setCookie(['flash' => "key=move&id=$uid&client_id=$cid"], ['flash'], true);
                        $relocate = true;
                    } else { //admin releasing an employee OR updating name 
                        $this->setCookie(['flash' => "key=leave&id=$uid&client_id=$cid"], ['flash'], true);
                        $relocate = !$cid ? true : $relocate;
                        $fail = $this->traitorCheck($cid, $dbrecord, $postdata);
                        if ($fail) {
                            $cookiearg = false;
                            if (!$relocate) {
                                $key = $this->self ? 'traitor' : '_traitor';
                                $this->setCookie(['flash' => "key=$key"], ['flash'], true);
                            }
                        }
                    }
                    if ($relocate) {
                        $data = $cookiearg ? $postdata : $_COOKIE;   

                        dump($data);
                        $this->setCookie($data, ['name', 'email', 'client_id'], $cookiearg);
                        reLocate($this->exit);
                    }
                    return $postdata;
                } else { //new
                    return $this->setClientEmail($cid, $postdata, []);
                }
            }; //default function
        }
        return function (?int $cid, array $postdata, int $id = 0) use ($uid, $relocate, $cookiearg, $dbrecord) {
            $postdata = $this->setClientEmail($cid, $postdata, $dbrecord);
            $fail = $this->traitorCheck($cid, $dbrecord, $postdata);
            if ($fail) {
                $cookiearg = false;
                $key = $this->self ? 'traitor' : '_traitor';
                $this->setCookie(['flash' => "key=$key"], ['flash'], true);
                $relocate = true;
            }
            if ($relocate) {
                $data = $cookiearg ? $postdata : $_COOKIE;
                $this->setCookie($data, ['name', 'email', 'client_id'], $cookiearg);
                reLocate($this->exit);
            }
            return $postdata;
        };
    }
}
