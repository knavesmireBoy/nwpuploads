<?php

namespace PoloAfrica\Controllers;

use \Ninja\DatabaseTable;

class Uploader
{
    public function __construct(private DatabaseTable $table, private DatabaseTable $usertable) {}

    public function getfiles(string $userid)
    {
        $user = $this->usertable->find('id', $userid);
        $user = $user[0] ?? null;
        $details = $user->getDetails();
        $ret = [];

        if(isApproved($details['role'], 'ADMIN')){
            $files = $this->table->findAll();
            foreach($files as $file){
                $user = $this->usertable->find('id', $file->userid)[0];
                $details = $user->getDetails();  
                unset($details['id']);
                unset($details['role']);
                $vars = array_merge(get_object_vars($file), $details);
                $vars['origin'] = substr($vars['file'], 11, 14);
                $ret[] = $vars;
            }
            dump($ret);
            return $ret;

        }
        else {
            
        }
    }
}
