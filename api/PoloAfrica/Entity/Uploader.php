<?php

namespace PoloAfrica\Entity;

class Uploader
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

    public function __construct(private \Ninja\DatabaseTable $table, private \Ninja\DatabaseTable $usertable) {}

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


    public function getDetails($prop = '')
    {
        $res = $this->table->find('userid', $this->userid);

        dump([$res, $this->userid]);
        $multi = count($res) > 1;
        $res = $res[0];
        if (!empty($res)) {
            if ($prop) {
                return $this->{$prop};
            }
           return ['id' => $res->id, 'name' => $res->name, 'email' => $res->email, 'client_id' => $res->client_id, 'editor' => $_SESSION['username'] == $res->email, 'multi' => $multi];
           $res = $this->table->findAll('userid', $this->userid);
        }
    }
}
