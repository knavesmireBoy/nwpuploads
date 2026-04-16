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

    public function getDetails($prop = '')
    {
        $res = $this->table->find('userid', $this->userid);
        $multi = count($res) > 1;
        $res = $this->fetch('usertable', 'id', $this->userid);
        dump([$multi, $res]);
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
        $multi = ['multi' => count($res) > 1];
        $user = $this->fetch('USERTABLE', 'id', $this->userid);
        
        if($user['client_id']){
            $client = $user->fetch('CLIENTTABLE', 'id', 'client_id');
            unset($client['id']);
        }
        $multi['editor'] = $res['email'] === $loggedin;
        return [...$res, ...$client, ...$multi];
    }
}
