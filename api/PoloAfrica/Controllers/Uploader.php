<?php

namespace PoloAfrica\Controllers;

use \Ninja\DatabaseTable;

class Uploader
{
    public function __construct(private DatabaseTable $table, private DatabaseTable $usertable) {}


    private function getUploadedFile()
    {
        $uploaded = function ($arg) {
            return $_FILES['upload'][$arg];
        };
        $uploadfile = $uploaded('tmp_name');
        $realname = $uploaded('name');
        $ext = preg_replace('/(.*)(\.[^0-9.]+$)/i', '$2', $realname);
        $time = time();
        //$uploadname = $time . getRemoteAddr() . $ext;
        $uploadname = $time . $ext;
        $filename =  FILESTORE . $uploadname;
        $filename =  "/tmp/$uploadname";
        return [$uploadfile, $uploadname, $filename, $realname];
    }

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

    private function presentList($role)
    {
        $users = [];
        $client = [];
        $all = $this->usertable->findAll();

        if (isApproved($role, 'ADMIN')) {

            foreach ($all as $row) {
                if (empty($row->client_id)) {
                    $users[$row->id] = $row->name;
                } else {
                    $client[$row->id] = $row->name;
                }
            }
            return [$users, $client];
        }
        return [[], []];
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
        list($users, $client) = $this->presentList($priv);
        $text = '';

        return [
            'template' => 'files.html.php',
            'title' => 'File Uploads',
            'variables' => [
                'files' => $files,
                'priv' => $priv,
                'user_id' => $userid,
                'pages' => $pages,
                'uhead' => '',
                'error' => '',
                'start' => 0,
                'display' => PAGINATE,
                'upload' => ASSET_UPLOAD . $userid,
                'disabled' => $priv === 'Browser' ? 'disabled' : '',
                'template' => $tmpl ? "$tmpl.html.php" : null,
                'users' => $users,
                'client' => $client,
                'predicates' => [partial('preg_match', '/^nwp/')],
                'text' => $text
            ]
        ];
    }

    public function upload(string $userid)
    {
        return $this->getfiles($userid, 'upload');
    }

    public function uploadSubmit()
    {
        list($nwpuploadfile, $nwpuploadname, $nwpfilename, $realname) = $this->getUploadedFile();

        // Copy the file (if it is deemed safe)
        if (!copy($nwpuploadfile, $nwpfilename)) {
            $error = "Could not save file as $nwpfilename!";
            include TEMPLATE . 'error.html.php';
            exit();
        } else {
            $key = $_POST['user'] ? $_POST['user'] : $_POST['key'];
            $dofile = function ($arg) {
                return $_FILES['upload'][$arg];
            };
            $values = ['filename' => $realname, 'mimetype' => $dofile('type'), 'description' => $_POST['desc'] ?? '', 'filepath' => FILESTORE, 'file' => $nwpuploadname, 'size' => $dofile('size') / 1024, 'userid' => $key, 'time' => date('Y-m-d')];
            dump($this->table->save($values, true));
            reLocate('/upload/getfiles/');
        }
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
