<?php

namespace PoloAfrica\Entity;

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


    private function getClientFiles($ownerid, $flag = false)
    {
        $user = $this->usertable->find('id', $ownerid)[0];
        $files = [];
        if ($user) {
            $users = $this->usertable->find('client_id', $user->client_id);
            $userids = array_map(fn($o) => $o->id, $users);
            $cb = curry2('in_array')($userids);
            $all = $this->table->findAll();
            foreach ($all as $file) {
                if ($cb($file->userid)) {
                    $files[] = $file;
                }
            }
        }
        return $flag ? count($files) : $files;
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
            $res = $this->table->findAll('userid', $this->userid);
        }
    }

    public function getData($loggedin)
    {
        $res = $this->table->find('userid', $this->userid);
        $tmp = 0;
        $max = count($res) > 1 ? 1 : 0;
        $res = $this->fetch('USERTABLE', 'id', $this->userid);
        if ($res['client_id']) {
            $user = $this->fetch('usertable', 'id', $this->userid);
            $client = $user->fetch('clienttable', 'id', $res['client_id']);
            $client = ['domain' => $client->domain, 'clientname' => $client->name];
            $tmp = $this->getClientFiles($this->userid, true);
            $tmp = count($tmp) > $max ? 2 : 0;
            $max += $tmp;
        }
        $multi = ['multi' => $max];
        $multi['editor'] = $res['email'] === $loggedin;
        return [...$res, ...$client, ...$multi];
    }
}
