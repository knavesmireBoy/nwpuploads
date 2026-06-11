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
    protected function validateDomField($cid, $record, $postdata, $insertId = 0)
    {
        list($name, $dom, $com) = $this->parseEmail($record['email']);
        list($ename, $edom, $ecom) = $this->parseEmail($postdata['email']);

        // $postdata = $this->setClientEmail($cid, $postdata, $record);

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
        list($name, $edom, $ecom) = $this->parseEmail($postdata['email']);
        $f = $cid ? $f : 'identity';
        $filter = curry2('array_filter')($f);
        $clients = $this->clienttable->findAll();

        dump([$dom, $edom, $cid]);

        $traitorCheck = composer(partial('in_array', $edom), $filter, partial('array_values'), partial('array_map', curry2('getter')(0)), partial('array_map', 'parseEmail'), partial('array_column', $clients));

        return $traitorCheck('domain');
    }

    public function updateDomain($uid, $default)
    {
        $relocate = null;
        $cookiearg = true;
        $dbrecord = $this->fetch('TABLE', 'id', $this->id);
        if ($default) {
            return function (?int $cid, array $postdata, int $id = 0) use ($uid, $relocate, $cookiearg, $dbrecord) {
                $relocate = false;
                $traitor = $this->traitorCheck($cid, $dbrecord, $postdata);
                $postdata = $this->validateDomField($cid, $dbrecord, $postdata);


                if ($traitor) {
                    $arg = $_SESSION['role'] === 'Admin' ? '_traitor' : 'domainchange';
                    reLocate("/user/load/$arg");
                }

                if (!$postdata) {
                    $arg = $_SESSION['role'] === 'Admin' ? '_leave' : 'domainchange';
                    reLocate("/user/load/$arg");
                }

                if (isset($dbrecord['email'])) { //existing
                    //can only be admin moving an employee; admin users cannot be moved
                    if ($cid) {
                        if ($cid != $dbrecord['client_id']) {
                            $this->setCookie(['flash' => "key=move&id=$uid&client_id=$cid"], ['flash'], true);
                            $relocate = true;
                        }
                    } else { //admin releasing an employee OR updating name 
                        $this->setCookie(['flash' => "key=leave&id=$uid&client_id=$cid"], ['flash'], true);
                        $relocate = true;
                    }
                    if ($relocate) {
                        $postdata = $this->setClientEmail($cid, $postdata, $dbrecord);
                        //not we need a falsy but non null value for the cookie to work
                        //otherwise client_id should be an int OR null
                        $this->setCookie([...$postdata, 'client_id' => $cid ?? 0], ['name', 'email', 'client_id'], true);
                        reLocate($this->exit);
                    } else {
                        return $this->setClientEmail($cid, $postdata, $dbrecord);
                    }
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
