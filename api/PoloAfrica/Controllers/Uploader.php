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
        $priv = $details['role'];
        $files = [];
        if(isApproved($priv, 'ADMIN')){
            $all = $this->table->findAll();
            foreach($all as $file){
                $user = $this->usertable->find('id', $file->userid)[0];
                $details = $user->getDetails();
                $name = $details['name'];
                unset($details['id']);
                unset($details['role']);
                unset($details['name']);
                $vars = array_merge(get_object_vars($file), $details, ['user' => $name]);
                $vars['origin'] = substr($vars['file'], 11, 14);
                $files[] = $vars;
            }

        }
        else {
            
        }

        return [
            'template' => 'files.html.php',
            'title' => 'File Uploads',
            'variables' => [
                'files' => $files,
                'priv' => $priv
            ]
        ];
    }
}
