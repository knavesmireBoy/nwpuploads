<?php

namespace NorthWolds\Controllers;

use \Ninja\DatabaseTable;

class Uploader
{
    private $files = [];
    public function __construct(private DatabaseTable $table, private DatabaseTable $usertable, private int $display, private int $start, private int $pages, private string $home) {}

    private function remove($path)
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }

    private function displayer($userId, $priv, $searchText, $owner = [], $customVars = [], $error = '')
    {
        list($users, $clients) = $this->presentList($priv);
        $thead = '';
        $fhead = '';
        $uhead = '';
        $defaultVars = [
            'files' => array_slice(toObject($this->files, true), $this->start, $this->display),
            'priv' => $priv,
            'pages' => $this->pages,
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
            'error' => '',
            'myip' => '',
            'goto' => '',
            'owner' => $owner,
            'key' => $userId,
            'searchtext' => $searchText ? $searchText : '',
            'user_id' => '',
            'text' => '',
            'ext' => ''
        ];
        $vars = array_merge($defaultVars, $customVars);
        if ($vars['searchtext']) {
            $vars['searchform'] = true;
        }
        return [
            'template' => 'files.html.php',
            'title' => 'File Uploads',
            'variables' => $vars
        ];
    }

    private function doUpdate($data)
    {
        $owner = intval($data['original']);
        $answer = $data['answer'];
        unset($data['answer']);
        unset($data['original']);
        if ($answer === 'No') {
            $record = $this->table->find('id', $data['id'], null, 0, 0, \PDO::FETCH_ASSOC);
            $record = $record[0] ?? [];
            if (!$record === []) {
                reLocate('/uploader/load/');
            }
            $record['userid'] = $data['user'];
            if (isset($data['user'])) {
                $record['userid'] = $data['user'];
                unset($data['user']);
            }
            $this->table->save([...$record, ...$data]);
        } else {
            $records = $this->table->findAll(null, 0, 0, \PDO::FETCH_ASSOC);
            foreach ($records as $record) {
                if ($record['userid'] === $owner && isset($data['user'])) {
                    $record['userid'] = intval($data['user']);
                    $this->table->save($record);
                }
            }
        }
    }

    private function prepUpdate($data)
    {
        $file = $this->table->find('id', $data['id'] ?? 0);
        $file = $file[0] ?? null;
        if (!isset($_SESSION['username'])) {
            reLocate(REG);
        }
        $user = $this->usertable->find('email', $_SESSION['username'])[0];
        $details = $user->getDetails();
        $priv = $details['role'];
        $all = [];
        if ($priv === 'Admin') {
            $users = $this->usertable->findAll();
            foreach ($users as $u) {
                $all[$u->id] = $u->name;
            }
        }
        $swap = $data['answer'] ?? 'No';
        $payload = ['users' => $all, 'answer' => $swap, 'button' => 'Update', 'filename' => $file->filename, 'description' => $file->description];
        return $this->load('update', [...$_POST, ...$payload]);
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

        $lib = [
            'search' => ['template' => '_search.html.php', 'zero' => null, 'action' => '/uploader/finder/', 'searchform' => true],

            'upload' => ['template' => 'upload.html.php'],

            'delete' => ['id' => $data['id'] ?? '', 'template' => 'prompt.html.php', 'title' => 'Prompt', 'prompt' => "Are you sure you want to delete this file?", 'call' => 'confirm', 'pos' => 'Yes', 'neg' => 'No', 'action' => '/uploader/confirm/'],

            'confirm' => ['id' => $data['id'] ?? '', 'template' => 'prompt.html.php', 'title' => 'Prompt', 'prompt' => "Select the extent of deletions", 'delete' => 'proceed',  'action' => '/uploader/destroy/'],

            'edit' => ['id' => $data['id'] ?? '', 'pos' => 'Yes', 'neg' => 'No', 'action' => $ismulti ? '/uploader/swap/' : '/uploader/edit/', 'call' => 'update', 'prompt' => $ismulti ? "Change ownership on ALL files?" : "Proceed to Update", 'template' => 'prompt.html.php'],

            'update' => ['id' => $data['id'] ?? '', 'button' =>  $data['button'] ?? '', 'all_users' => $data['users'] ?? [], 'colleagues' => $data['colleagues'] ?? [], 'answer' => $data['answer'] ?? '', 'action' => '/uploader/update/', 'template' => 'update.html.php', 'title' => 'Update', 'filename' => $data['filename'] ?? '', 'description' => $data['description'] ?? '']
        ];

        if ($key && isset($lib[$key])) {
            $ret = $lib[$key];
        }
        if ($key !== 'delete' || $key !== 'upload') {
            return [...$ret, ...$owner];
        }
        return $ret;
    }

    private function prepFileForDisplay(array $file, \NorthWolds\Entity\User $user)
    {
        $details = $user->getDetails();
        $name = $details['name'];
        unset($details['id']);
        unset($details['role']);
        unset($details['name']);
        unset($details['client_id']);
        $vars = array_merge($file, $details, ['user' => $name]);
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
        if (!isset($_SESSION['username'])) {
            reLocate(REG);
        }
        $user = $this->usertable->find('email', $_SESSION['username'])[0];
        $details = $user->getDetails();
        $priv = $details['role'];
        $cid = $details['client_id'];
        $cb = $this->validateFile($priv, $cid, $user->id);

        if (empty($this->files) || $key === 'clear') {
            $this->files = $this->table->findAll();
            $this->pages = $this->setPages(count($this->files));
            $displayFiles = $this->table->findAll(null, $this->display, $this->start);
        }

        $files = [];
        $owner = [];
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
                $files[] = $this->prepFileForDisplay(get_object_vars($file), $o);
            }
        }
        $this->files = $files;
        return $this->displayer($user->id, $priv, '', $owner, $customVars);
    }

    public function upload(string $userid)
    {
        return $this->load('upload', []);
    }

    public function updateSubmit()
    {
        return $this->doUpdate($_POST);
    }

    public function editSubmit()
    {
        //proceed to update
        if (isset($_POST['update']) && $_POST['update'] === 'No') {
            reLocate('/uploader/load');
        } else {
            return $this->prepUpdate($_POST);
        }
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

    public function nav($s, $search, $u = '', $t = '', $x = '', $sort = '')
    {
        $this->start = intval($s);
        $srch = intval($search);
        $hire = [];
        $args = [];
        if ($srch) {
            if ($srch & 1) {
                $args[] = $u;
            } else {
                $hire[] = $u;
                $args[] = '';
            }
            if ($srch & 2) {
                if (isset($hire[0])) {
                    $args[] = array_shift($hire);
                    $hire[] = $t;
                } else {
                    $args[] = $t;
                }
            } else {
                $hire[] = $t;
                $args[] = '';
            }
            if ($srch & 4) {
                if (isset($hire[0])) {
                    $args[] = array_shift($hire);
                    $hire[] = $x;
                } else {
                    $args[] = $x;
                }
            } else {
                $hire[] = $x;
                $args[] = '';
            }
            dump($args);
            return $this->found(...$args);
        }
        return $this->load();
    }

    public function find()
    {
        return $this->load('search');
    }
    //form submission
    public function finder()
    {
        return $this->found($_GET['user'], $_GET['text'], $_GET['ext']);
    }

    private function found($user_id, $text, $ext)
    {
        if (!isset($_SESSION['username'])) {
            reLocate(REG);
        }
        $user = $this->usertable->find('email', $_SESSION['username'])[0];
        $details = $user->getDetails();
        $priv = $details['role'];
        $file = $this->table->getEntity();
        $pos = curry2('strpos');
        $files = [];
        $getExt = composer('strtolower', curry2('substr')(1), curry2('strrchr')('.'));
        $records = $this->table->findAll(null, 0, 0, \PDO::FETCH_ASSOC);
        if ($user_id) {
            $user = $this->usertable->find('id', $user_id)[0];
            $details = $user->getDetails();
            if ($priv == 'Admin') {
                if (isset($details['client_id'])) {
                    $records = toObject($file->getClientFiles($user_id), true);
                } else {
                    $records = $this->table->find('userid', $user_id, null, 0, 0, \PDO::FETCH_ASSOC);
                    $records = $records[0] ?? [];
                }
            } else { //multi client
                $records = toObject($file->getClientFiles($user_id), true);
                if (empty($records)) {
                    $records = $this->table->find('userid', $user_id, null, 0, 0, \PDO::FETCH_ASSOC);
                }
            }
        }

        if ($text) { // Some search text was specified 
            $byText = composer('is_numeric', $pos($text), curry2('getter')('filename'));
            $records = safeFilter($records, $byText);
        }

        if ($ext) {
            $sub = curry2('substr')(1);
            $pos = curry2('strrchr')('.');
            $contains = curry2('in_array')(['pdf', 'zip', 'jpg']);
            $find = composer(negate('identity'), $contains, 'strtolower', curry2('substr')(1), curry2('strrchr')('.'), curry2('getter')('filename'));
            if ($ext === 'owt') {
                $records = safeFilter($records, $find);
            } else {
                $eq = partial('equals', $ext);
                $byExt = composer($eq, $sub, $pos, curry2('getter')('filename'));
                $records = safeFilter($records, $byExt);
            }
        }
        foreach ($records as $file) {
            $o = $this->usertable->find('id', $file['userid'])[0];
            $files[] = $this->prepFileForDisplay($file, $o);
        }
        $this->pages = $this->setPages(count($files));
        $this->files = $files;
        //$this->pages = $pages;
        return $this->displayer($user->id, $priv, 'Clear Search Results', [], ['user_id' => $user_id, 'text' => $text, 'ext' => $ext]);
    }
}
