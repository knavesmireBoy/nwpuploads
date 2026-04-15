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

    public function __construct(private \Ninja\DatabaseTable $table, private \Ninja\DatabaseTable $usertable)
    {
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
  

    public function getDetails() {
        return $this->fetch('usertable', 'userid', $this->id);

    }
}
