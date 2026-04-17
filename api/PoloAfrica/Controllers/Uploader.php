<?php

namespace PoloAfrica\Controllers;

use \Ninja\DatabaseTable;

class Uploader
{

    public function __construct(private DatabaseTable $table, private DatabaseTable $usertable, private int $display, private int $start, private int $pages) {}

    private function remove($path)
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }

    private function getErrors($key)

    {
        $lib = ['missing' => 'File could not be found'];
        return $lib[$key] ?? '';
    }

    private function getClientFiles($ownerid)
    {
        $files = [];
        $user = $this->usertable->find('id', $ownerid)[0];
        if (!$user->client_id) {
            return $files;
        }
        $users = $this->usertable->find('client_id', $user->client_id);
        $userids = array_map(fn($o) => $o->id, $users);
        $cb = curry2('in_array')($userids);
        $all = $this->table->findAll();
        foreach ($all as $file) {
            if ($cb($file->userid)) {
                $files[] = $file;
            }
        }
        return $files;
    }
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

    private function getCustomVars($key, $data)
    {
        //if($key === 'confirm') dump($data);
        $ret = [];
        $ismulti = !empty($data['multi']);
        $owner = ['ownerid' => $data['ownerid'] ?? '', 'ownername' => $data['ownername'] ?? '', 'domain' => $data['domain'] ?? '', 'multi' => $data['multi'] ?? '', 'editor' => $data['editor'] ?? '', 'clientname' => $data['clientname'] ?? ''];

        $lib = ['delete' => ['id' => $data['id'] ?? '', 'template' => 'prompt.html.php', 'title' => 'Prompt', 'prompt' => "Are you sure you want to delete this file?", 'call' => 'confirm', 'pos' => 'Yes', 'neg' => 'No', 'action' => '/uploader/confirm/'], 'confirm' => ['id' => $data['id'] ?? '', 'template' => 'prompt.html.php', 'title' => 'Prompt', 'prompt' => "Select the extent of deletions", 'delete' => 'proceed',  'action' => '/uploader/destroy/'], 'upload' => ['template' => 'upload.html.php'], 'edit' => ['id' => $data['id'] ?? '', 'pos' => 'Yes', 'neg' => 'No', 'action' => '/uploader/update/', 'call' => 'update', 'prompt' => $ismulti ? "Change ownership on ALL files?" : "Proceed to Update", 'template' => 'prompt.html.php']];

        if ($key && isset($lib[$key])) {
            $ret = $lib[$key];
        }
        if ($key === 'confirm' || $key === 'edit') {
            return [...$ret, ...$owner];
        }
        return $ret;
    }

    private function prepFileForDisplay($file, $user)
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

    public function read($id = '')
    {
        $disposition = $id ? 'inline' : 'attachment';
        $id = $id ? $id : (isset($_POST['id']) ? $_POST['id'] : null);
        $file = $this->table->find('id', $id);
        $file = $file[0] ?? null;
        if (!$file) {
            $error = 'File with specified ID not found in the database!';
            include TEMPLATE . 'error.html.php';
            exit();
        }
        $filename = $file->filename;
        $mimetype = $file->mimetype;
        $filepath = $file->filepath;
        $uploadfile = $file->file;
        $size = $file->size;
        $filepath .= $uploadfile;
        if (!file_exists($filepath)) {
            reLocate('/uploader/load/missing');
            // reLocate(BBC);
        }
        $filedata = file_get_contents($filepath);
        // $disposition = $_GET['action'] == 'download' ? 'attachment' : 'inline';
        //$mimetype = 'application/x-unknown'; application/octet-stream
        //Content-type must come before Content-disposition
        header("Content-type: $mimetype");
        //this works..
        header('Content-disposition: ' . $disposition . '; filename=' . '"' . $filename . '"');
        //header("Content-Transfer-Encoding: binary");
        header('Content-length:' . strlen($filedata));
        echo $filedata;
        exit();
    }

    private function sorter()
    {
        $sorter = array('f' => 'filename ASC', 'ff' => 'filename DESC', 'u' => 'name ASC', 'uu' => 'name DESC', 'uf' => 'name ASC, filename ASC', 'uuf' => 'name DESC, filename ASC',  'uff' => 'name ASC, filename DESC',  'uuff' => 'name DESC, filename DESC', 'ut' => 'name ASC, time ASC', 'utt' => 'name ASC, time DESC', 'uut' => 'name DESC, time ASC', 'uutt' => 'name DESC, time DESC', 't' => 'time ASC', 'tt' => 'time DESC');

        $mainclass = $this->pages === 1 ? '' : 'paginate';

        if (isset($_GET['s']) && is_numeric($_GET['s'])) {
            $start = $_GET['s'];
        } else {
            $start = 0;
        }

        $sort = $_GET['sort'] ?? '1';

        foreach ($sorter as $k => $v) {
            if ($k == $sort) break;
        }
        switch ($sort) {
            case $k:
                $order_by = $sorter[$k];
                break;
            default:
                $order_by = 'time DESC';
                $sort = 'tt';
                break;
        }
    }

    public function load(string $key = '', array $vars = [])
    {
        $user = $this->usertable->find('email', $_SESSION['username'])[0];
        $details = $user->getDetails();
        $priv = $details['role'];
        $cid = $details['client_id'];
        $files = [];
        $owner = [];
        $total = count($this->table->findAll());
        $displayFiles = $this->table->findAll(null, $this->display, $this->start);
        $cb = $this->validateFile($priv, $cid, $user->id);

        $customVars = [];
        //$customVars: vars for prompts
        $error = $this->getErrors($key);
        if (!$error) {
            $customVars = $this->getCustomVars($key, $vars);
        }

        if (isset($vars['id'])) {
            $file = $this->table->find('id', $vars['id']);
            $file = !empty($file) ? $file[0] : null;
            if ($file) {
                $data = $file->getData($_SESSION['username']);
                $client = $this->usertable->find('client_id', $data['client_id'] ?? 0);
                $client = !empty($client) ? $client[0] : null;
                if ($client) {
                    $owner = [...$data, ...$client->getDetails()];
                } else {
                    $owner = $data;
                }
            }
        }
        foreach ($displayFiles as $file) {
            $o = $this->usertable->find('id', $file->userid)[0];
            if ($cb($file->userid)) {
                $files[] = $this->prepFileForDisplay($file, $o);
            }
        }
        $pages = $this->setPages($total);
        list($users, $clients) = $this->presentList($priv);
        //vars used by search/pagination
        $text = '';
        $suffix = '';
        $user_id = '';
        $ext = '';
        $byuser = '';
        $bytext = '';
        $thead = '';
        $fhead = '';
        $uhead = '';

        $defaultVars = [
            'files' => $files,
            'priv' => $priv,
            'pages' => $pages,
            'fhead' => $fhead,
            'thead' => $thead,
            'uhead' => $uhead,
            'error' => $error,
            'start' => $this->start,
            'display' => $this->display,
            'upload' => ASSET_UPLOAD,
            'disabled' => $priv === 'Browser' ? 'disabled' : '',
            'users' => $users,
            'clients' => $clients,
            'predicates' => [partial('preg_match', '/^nwp/')],
            'user_id' => $user_id,
            'text' => $text,
            'suffix' => $suffix,
            'ext' => $ext,
            'bytext' => $bytext,
            'byuser' => $byuser,
            'error' => '',
            'myip' => '',
            'goto' => '',
            'owner' => $owner,
            'key' => $user->id
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
        return $this->load('upload', []);
    }

    public function update()
    {
        dump($_POST);
        $user = $this->usertable->find('email', $_SESSION['username'])[0];
        $details = $user->getDetails();
        $priv = $details['role'];
        $all_users = [];
        if ($priv === 'Admin') {
            $users = $this->usertable->findAll();
            foreach ($users as $u) {
                $all_users[$u->id] = $u->name;
            }
        }
        dump($all_users);
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
            reLocate("/uploader/load/");
        }
    }

    private function setPages($records)
    {
        $pages = 1;
        if ($records > $this->display) {
            $pages = ceil($records / $this->display);
        }
        $this->pages = $pages;
        return $pages;
    }

    public function delete()
    {
        return $this->load('delete', $_POST);
    }

    public function confirm()
    {
        if (isset($_POST['confirm']) && $_POST['confirm'] === 'Yes') {
            return $this->load('confirm', $_POST);
        } else {
            return $this->load('edit', $_POST);
        }
    }

    public function destroy()
    {
        reLocate("/uploader/load/");
    }

    public function destroySubmit()
    {
        $userfiles = [];
        $clientfiles = [];
        $ownerid = $_POST['ownerid'] ?? '';
        if ($ownerid) {
            $userfiles = $this->table->find('userid', $ownerid);
            $clientfiles = $this->getClientFiles($ownerid);
        }

        $lib = ['f' => $this->table->find('id', $_POST['id']), 'u' => $userfiles, 'c' => $clientfiles];
        $k = $_POST['extent'] ?? '';
        if (isset($lib[$k])) {
            $files = $lib[$k];
            foreach ($files as $file) {
                $this->table->delete('id', $file->id);
                $this->remove(FILESTORE . $file->file);
            }
        } else {
            header('Location: .');
            exit();
        }
    }

    public function nav($s, $p, $search, $sort)
    {
        $this->start = $s;
        $this->pages = $p;
        return $this->load();
    }
}
