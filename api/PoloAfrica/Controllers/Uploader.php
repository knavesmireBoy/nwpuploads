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

        $pages = $this->setPages($priv);

        return [
            'template' => 'files.html.php',
            'title' => 'File Uploads',
            'variables' => [
                'files' => $files,
                'priv' => $priv,
                'pages' => $pages,
                'uhead' => '',
                'error' => '',
                'predicates' => [partial('preg_match', '/^nwp/')]
            ]
        ];
    }

    private function setPages($priv)
    {
        $pages = 1;
        include CONNECT;
        $nwpsql = "SELECT COUNT(upload.id) as total from upload ";
        if (preg_match("/client/i", $priv)) {
            $nwptmp = " INNER JOIN usr ON upload.userid = usr.id WHERE usr.email=:email";
        }
    
        if (isset($nwptmp)) {
            $nwpsql .= $nwptmp;
            $nwpst = $pdo->prepare($nwpsql);
            $nwpst->bindValue(":email", $_SESSION['email']);
        } else {
            $nwpst = $pdo->prepare($nwpsql);
        }
        doPreparedQuery($nwpst, "Database error requesting the list of files:", false);
        $nwprow = $nwpst->fetch(\PDO::FETCH_ASSOC);
    

        $records = $nwprow['total'];
        if ($records > PAGINATE) {
            $pages = ceil($records / PAGINATE);
        }
        return $pages;
    }
}
