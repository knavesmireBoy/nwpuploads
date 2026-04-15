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

    private function getCustomVars($key, $id = 0)
    {
        $lib = ['delete' => ['fileid' => $id, 'template' => 'prompt.html.php', 'title' => 'Prompt', 'prompt' => "Are you sure you want to delete this file?", 'call' => 'confirm', 'pos' => 'Yes', 'neg' => 'No', 'action' => '']];

        if (isset($lib[$key])) {
            return $lib[$key];
        }
        return [];
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

    public function load(string $key = '', string $fileid = '')
    {
        $user = $this->usertable->find('email', $_SESSION['username'])[0];
        $details = $user->getDetails();
        $priv = $details['role'];
        $cid = $details['client_id'];
        $files = [];
        $owner = [];
        $all = $this->table->findAll();
        $cb = $this->validateFile($priv, $cid, $user->id);
        $customVars = $this->getCustomVars($key, $fileid);
        if($fileid){
            $file = $this->table->find('id', $fileid)[0];
            $data = $file->getData($_SESSION['username']);
            $client = $this->usertable->find('client_id', $data['client_id'])[0];
            $owner = [...$data, ...$client->getDetails()];
            /*
            $owner = ['id' => $data['id'], 'name' => $data['name'],'domain' => $data['domain'], 'multi' => $data['multi'], 'editor' => $data['editor']];
            */
        }
       

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
        $suffix = '';
        $error = '';
        $defaultVars = [
            'files' => $files,
            'priv' => $priv,
            'user_id' => $user->id,
            'pages' => $pages,
            'uhead' => '',
            'error' => '',
            'start' => 0,
            'display' => PAGINATE,
            'upload' => ASSET_UPLOAD,
            'disabled' => $priv === 'Browser' ? 'disabled' : '',
            'users' => $users,
            'client' => $client,
            'predicates' => [partial('preg_match', '/^nwp/')],
            'text' => $text,
            'suffix' => $suffix,
            'error' => '',
            'owner' => $owner
        ];
        $vars = array_merge($defaultVars, $customVars);
        return [
            'template' => 'files.html.php',
            'title' => 'File Uploads',
            'variables' => $vars
        ];
    }

    public function upload(string $userid)
    {
        return $this->load($userid, '', 'upload');
    }

    public function uploadSubmit()
    {
        list($uploadfile, $uploadname, $filename, $realname) = $this->getUploadedFile();

        // Copy the file (if it is deemed safe)
        if (!copy($uploadfile, $filename)) {
            $error = "Could not save file as $filename!";
            include TEMPLATE . 'error.html.php';
            exit();
        } else {
            $userid = !empty($_POST['user']) ? $_POST['user'] : $_POST['key'];
            $description = isset($_POST['desc']) ? $_POST['desc'] : '';
            $dofile = function ($arg) {
                return $_FILES['upload'][$arg];
            };
            $size = $dofile('size') / 1024;
            $time = date('Y-m-d');
            $mimetype = $dofile('type');

            $values = ['filename' => $realname, 'mimetype' => $mimetype, 'description' => $description, 'filepath' => FILESTORE, 'file' => $uploadname, 'size' => $size, 'userid' => $userid, 'time' => $time];

            $this->table->save($values, true);
            $key = $_POST['key'];
            reLocate("/uploader/load/$key");
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

    public function delete()
    {
        return $this->load('delete', $_POST['id']);
    }
}
