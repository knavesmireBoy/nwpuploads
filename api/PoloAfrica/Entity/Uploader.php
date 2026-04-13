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

    public function getDetails() {


    }
}
