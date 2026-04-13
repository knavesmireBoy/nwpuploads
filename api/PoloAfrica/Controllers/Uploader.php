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
        $name = $details['name'];
        $cid = $details['client_id'];
        unset($details['id']);
        unset($details['role']);
        unset($details['name']);
        unset($details['client_id']);
        $files = [];
        $all = $this->table->findAll();
        $total = count($all);
        $pages = $this->setPages($total);
        if (isApproved($priv, 'ADMIN')) {
            foreach ($all as $file) {
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
        } else if ($cid) {
                dump($all);

        }

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
