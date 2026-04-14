<?php

namespace PoloAfrica\Controllers;

use \Ninja\DatabaseTable;

class Uploader
{
    public function __construct(private DatabaseTable $table, private DatabaseTable $usertable) {}


    private function prepfiles($file, $user)
    {
        $details = $user->getDetails();
        $name = $details['name'];
        unset($details['id']);
        unset($details['role']);
        unset($details['name']);
        unset($details['client_id']);
        $vars = array_merge(get_object_vars($file), $details, ['user' => $name]);
        $vars['origin'] = substr($vars['file'], 11, 14);
        return $vars;
    }

    private function presentList()
    {
        $users = [];
        $client = [];
        $all = $this->usertable->findAll();
        return safeFilter($all, fn($o)=> empty($o['client_id']));
    }

    private function validateFile($priv, $cid, $userid)
    {
        if (isApproved($priv, 'ADMIN')) {
            return 'identity';
        } else if ($cid) {
            $users = $this->usertable->find('client_id', $cid);
            $userids = array_map(fn($o) => $o->id, $users);
            return curry2('in_array')($userids);
        } else {
            return curry2('equals')($userid);
        }
    }

    public function getfiles(string $userid, string $tmpl = '')
    {
        $user = $this->usertable->find('id', $userid)[0];
        $details = $user->getDetails();
        $priv = $details['role'];
        $cid = $details['client_id'];
        $files = [];
        $all = $this->table->findAll();
        $cb = $this->validateFile($priv, $cid, $userid);
        /*


        if (isApproved($priv, 'ADMIN')) {
            foreach ($all as $file) {
                $user = $this->usertable->find('id', $file->userid)[0];
                if($cb($user)){
                    $files[] = $this->prepfiles($file, $user);
                }
                
            }
        } else if ($cid) {
            $users = $this->usertable->find('client_id', $cid);
            $userids = array_map(fn($o) => $o->id, $users);
            foreach ($all as $file) {
                if (in_array($file->userid, $userids)) {
                    $user = $this->usertable->find('id', $file->userid)[0];
                    $files[] = $this->prepfiles($file, $user);
                }
            }
        } else if(empty($files)){
            foreach ($all as $file) {
                if ($file->userid == $userid) {
                    $files[] = $this->prepfiles($file, $user);
                }
            }
        }
*/
        foreach ($all as $file) {
            $user = $this->usertable->find('id', $file->userid)[0];
            if ($cb($file->userid)) {
                $files[] = $this->prepfiles($file, $user);
            }
        }
        $total = count($files);
        $pages = $this->setPages($total);

        return [
            'template' => 'files.html.php',
            'title' => 'File Uploads',
            'variables' => [
                'files' => $files,
                'priv' => $priv,
                'pages' => $pages,
                'uhead' => '',
                'error' => '',
                'start' => 0,
                'display' => PAGINATE,
                'upload' => ASSET_UPLOAD . $userid,
                'disabled' => $priv === 'Browser' ? 'disabled' : '',
                'template' => $tmpl ? "$tmpl.html.php" : null,
                'users' => $this->presentList(),
                'client' => [],
                'predicates' => [partial('preg_match', '/^nwp/')]
            ]
        ];
    }

    public function upload(string $userid)
    {
        return $this->getfiles($userid, 'upload');
    }

    private function setPages($records)
    {
        $pages = 1;
        if ($records > PAGINATE) {
            $pages = ceil($records / PAGINATE);
        }
        return $pages;
    }
}
