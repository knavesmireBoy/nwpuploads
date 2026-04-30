<?php

namespace NorthWolds\Entity;

class Uploader extends Entity
{
    public $id;
    public $filename;
    public $mimetype;
    public $description;
    public $filepath;
    public $file;
    public $size;
    public $userid;
    public $time;

    public function __construct(protected \Ninja\DatabaseTable $table, protected \Ninja\DatabaseTable $usertable) {}


    public function getClientFiles($arg)
    {
        $files = [];
        if (is_numeric($arg)) {
            $user = $this->usertable->find('id', $arg)[0];
            if ($user) {
                $users = $this->usertable->find('client_id', $user->client_id);
            }
        } else {
            $user = $this->usertable->getEntity();
            $client = $user->fromDomain($arg);
            $users = $this->usertable->find('client_id', $client->id);
        }
        if (isset($client)) {
            $userids = array_map(fn($o) => $o->id, $users);
            $cb = curry2('in_array')($userids);
        } else {
            $cb = curry2('equals')(intval($arg));
        }
        $all = $this->table->findAll();
        foreach ($all as $file) {
            if ($cb($file->userid)) {
                $files[] = $file;
            }
        }
        return $files;
    }

    public function getDetails($prop = '')
    {
        $res = $this->table->find('userid', $this->userid);
        $multi = count($res) > 1;
        $res = $this->fetch('usertable', 'id', $this->userid);
        if (!empty($res)) {
            if ($prop) {
                return $this->{$prop};
            }
            return ['id' => $res->id, 'name' => $res->name, 'email' => $res->email, 'client_id' => $res->client_id, 'editor' => $_SESSION['username'] == $res->email, 'multi' => $multi];


           // $res = $this->table->findAll('userid', $this->userid);
        }
    }

    public function getData($loggedin)
    {
        $res = $this->table->find('userid', $this->userid);
        $tmp = 0;
        $count = count($res);
        $ret = $count > 1 ? 1 : 0;
        $res = $this->fetch('USERTABLE', 'id', $this->userid);
        if ($res['client_id']) {
            $tmp = count($this->getClientFiles($this->userid)) > $count ? 2 : 0;
            $ret += $tmp;
        }
        $multi = ['multi' => $ret];
        $multi['editor'] = $res['email'] === $loggedin;
        return [...$res, ...$multi];
    }
}
