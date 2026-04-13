<?php

namespace PoloAfrica\Controllers;

use \Ninja\DatabaseTable;

class Uploader
{
    public function __construct(private DatabaseTable $table, private DatabaseTable $usertable) {}


    private function prepfiles($file, $details)
    {
        $name = $details['name'];
        unset($details['id']);
        unset($details['role']);
        unset($details['name']);
        unset($details['client_id']);
        $vars = array_merge(get_object_vars($file), $details, ['user' => $name]);
        $vars['origin'] = substr($vars['file'], 11, 14);
        return $vars;
    }

    public function getfiles(string $userid)
    {
        $user = $this->usertable->find('id', $userid);
        $user = $user[0] ?? null;
        $details = $user->getDetails();
        $priv = $details['role'];
        $cid = $details['client_id'];
        $files = [];
        $all = $this->table->findAll();
        if (isApproved($priv, 'ADMIN')) {
            foreach ($all as $file) {
                $user = $this->usertable->find('id', $file->userid)[0];
                $files[] = $this->prepfiles($file, $details);
            }
        } else if ($cid) {
            $users = $this->usertable->find('client_id', $cid);
            $userids = array_map(fn($o) => $o->id, $users);
            foreach ($all as $file) {
                if (in_array($file->userid, $userids)) {
                    $files[] = $this->prepfiles($file, $details);
                }
            }
        } else {
            foreach ($all as $file) {
                if ($file->userid == $userid) {
                    $files[] = $this->prepfiles($file, $details);
                }
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
                'predicates' => [partial('preg_match', '/^nwp/')]
            ]
        ];
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
