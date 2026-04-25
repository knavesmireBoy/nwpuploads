<?php

namespace NorthWolds\Controllers;

use \Ninja\DatabaseTable;

class Uploader
{
    private $sort = 'tt';
    public function __construct(private DatabaseTable $table, private DatabaseTable $usertable, private int $display, private int $start, private int $pages, private string $home) {}

    private function remove($path)
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }

    private function displayer($userId, $priv, $displayfiles, $searchText, $owner = [], $customVars = [], $error = '')
    {
        list($users, $clients) = $this->presentList($priv);


        $defaultVars = [
            'files' => $displayfiles,
            'priv' => $priv,
            'pages' => $this->pages,
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
            'ext' => '',
            'fhead' => 'f',
            'uhead' => 'u',
            'thead' => 't',
            'sort' => $this->sort
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

            'sort' => ['fhead' => $data['fhead'] ?? '', 'uhead' => $data['uhead'] ?? '', 'thead' => $data['thead'] ?? ''],

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


    private function prepFileForDisplay($records, $cb)
    {
        $ret = [];
        $callback = function (array $file, \NorthWolds\Entity\User $user) {
            $details = $user->getDetails();
            $name = $details['name'];
            unset($details['id']);
            unset($details['role']);
            unset($details['name']);
            unset($details['client_id']);
            $vars = array_merge($file, $details, ['user' => $name]);
            $vars['origin'] = substr($vars['file'], 11, 14);
            return $vars;
        };

        foreach ($records as $file) {
            $o = $this->usertable->find('id', $file['userid'])[0];
            if ($cb($file['userid'])) {
                $ret[] = $callback($file, $o);
            }
        }
        return $ret;
    }

    private function presentList($role)
    {
       // $users = [];
       // $client = [];
        $tmp = [];
        $pairs = [];
        $all = $this->usertable->findAll();

        if (isApproved($role, 'ADMIN')) {
            foreach ($all as $k => $row) {
                if (empty($row->client_id)) {
                    $pairs[] =  $row->id;
                    $pairs[] = $row->name;
                   // $users[$row->id] = $row->name;
                } else {
                    $u = $this->usertable->find('id', $row->id)[0];
                    $details = $u->getDetails();
                    $tmp[$k]['domain'] = $details['domain'];
                    $tmp[$k]['name'] = $details['clientname'];
                }
            }
            array_multisort(array_column($pairs, 'name'), SORT_ASC, $pairs);
            $users = pairsToKeyValue($tmp);
            array_multisort(array_column($tmp, 'name'), SORT_ASC, $tmp);
            $client = toKeyValue($tmp, 'domain', 'name');
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

    public function sort($state = '')
    {
        $this->sort = $state ? $state : $this->sort;
        $ufn = qUserHead('u');
        $tfn = qHead('t');
        $ffn = qHead('f', 'u');
        $fhead =  $ffn($state);
        $uhead =  $ufn($state);
        $thead =  $tfn($state);
        return $this->load('sort', ['fhead' => $fhead, 'uhead' => $uhead, 'thead' => $thead]);
    }

    private function sorter()
    {
        $sorter = array('f' => 'filename ASC', 'ff' => 'filename DESC', 'u' => 'name ASC', 'uu' => 'name DESC', 'uf' => 'name ASC, filename ASC', 'uuf' => 'name DESC, filename ASC',  'uff' => 'name ASC, filename DESC',  'uuff' => 'name DESC, filename DESC', 'ut' => 'name ASC, time ASC', 'utt' => 'name ASC, time DESC', 'uut' => 'name DESC, time ASC', 'uutt' => 'name DESC, time DESC', 't' => 'time ASC', 'tt' => 'time DESC');

        foreach ($sorter as $k => $v) {
            if ($k == $this->sort) break;
        }
        switch ($this->sort) {
            case $k:
                $order_by = $sorter[$k];
                break;
            default:
                $order_by = 'time DESC';
                $this->sort = 'tt';
                break;
        }
        return $order_by;
    }

    public function load(string $key = '', array $vars = [])
    {
        if (!isset($_SESSION['username'])) {
            reLocate(REG);
        }
        $contenders = [];
        $owner = [];
        $customVars = [];

        $srch = 8;
        $setcookie = doSetCookie(true);
        $setcookie('searched', $srch);

        $error = $this->getErrors($key);
        $user = $this->usertable->find('email', $_SESSION['username'])[0];
        $details = $user->getDetails();
        $priv = $details['role'];
        $cid = $details['client_id'];
        $cb = $this->validateFile($priv, $cid, $user->id);
        $all = $this->table->findAll(null, 0, 0, \PDO::FETCH_ASSOC);
        $this->pages = $this->setPages(count($all)); //assume $priv is Admin

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

        $orderby = $this->sorter();
        $order =  preg_match('/^name/i', $orderby) ? null : $orderby;
        //sub sort by time or file only involves one table `upload`
        if ($order) {
            // $all = $this->table->findAll(null, 0, 0, \PDO::FETCH_ASSOC);
            $contenders = $this->prepFileForDisplay($all, $cb);
        }
        //but sub sort by `user` can only be achieved with a JOIN which we are not supporting in this ORM version
        //https://stackoverflow.com/questions/1532218/life-without-joins-understanding-and-common-practices
        if (!$order) {
            $first = [];
            $last = [];
            $time = [];
            $file = [];
            $second = [];
            $lib = ['ASC' => SORT_ASC, 'DESC' => SORT_DESC];
            $contenders = $this->prepFileForDisplay($all, $cb);
            foreach ($contenders as $k => $v) {
                $u = explode(' ', $v['user']);
                $uk = randomID();
                /*assign unique key for retrieval (userid would only work if each user had only one file) otherwise earlier entries get overwritten and $first, $last and $contenders must match in length*/
                $first[$uk] = current($u);
                $last[$uk] = end($u) || '';
                $contenders[$k]['user'] = $u[1];
                $contenders[$k]['uniq'] = $uk; //assign same key to the `uniq` property
                $time[$k] = $v['time'];
                $file[$k] = $v['filename'];
            }
            if (strpos($orderby, ',')) {
                $second = strpos($orderby, 'time') ? $time : $file;
                preg_match_all('/[A-Z]+/', $orderby, $matches);
                list($a, $b) = $matches[0];
                $sort = [$lib[$a], $lib[$b]];
                array_multisort($last, $sort[0], $second, $sort[1], $contenders);
            } else {
                preg_match('/[A-Z]+/', $orderby, $matches);
                array_multisort($last, $lib[$matches[0]], $contenders);
            }
            foreach ($contenders as $k => $v) {
                $uk = $contenders[$k]['uniq'];
                $f = $first[$uk];
                $l = $last[$uk];
                $contenders[$k]['user'] = "$f $l";
            }
        }
        $this->pages = isApproved($priv, 'ADMIN') ? $this->pages : $this->setPages(count($contenders));
        //do this last; paginate
        $displayfiles = array_slice($contenders, $this->start, $this->display);
        return $this->displayer($user->id, $priv, $displayfiles, '', $owner, $customVars);
    }

    public function upload()
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

    public function nav($s, $p, $first = '', $second = '', $third = '', $fourth = '')
    {
        $this->start = intval($s);
        $this->pages = intval($p);
        $srch = intval($_COOKIE['searched'] ?? 0);

        $args = [];
        $hold = [];

        /* DOOZY
        the alternative to persistence in the QUERY_STRING (which can get ugly) is to pass data via function params OR cookies
        $empty = ''; $int = 47;
        but php will skip any empty params ie /$empty/$int php treats this as //$int
        so if only 2 params are defined they will be $first and $second; even though the values sent may be $third and $fourth
        function($empty, $int){
        $empty doesn't get passed instead the $empty binding is set to $int (47) the first non empty value
        we have REINSTATE the empty values in the correct order for sending on to the next function
        KEY to this is the $search param crucially converted to an int
        the value assigned to $search (an integer between 0 and 15 in this case) is interrogated by the bitwise operator (&)mto determine
        the position of empty and defined values
        if $srch = 1 only the the first param is defined; $srch = 2 only the second; 3 first and second;
        00000001 = 1 00000010 = 2 00000011 = 3
        }
        */

        $sortargs = function ($int, $arg) use ($srch, &$args, &$hold) {
            if ($srch & $int) {
                if (isset($hold[0])) {
                    $args[] = array_shift($hold);
                    $hold[] = $arg;
                } else {
                    $args[] = $arg;
                }
            } else {
                $hold[] = $arg;
                $args[] = '';
            }
        };

        if ($srch) {
            $payload = [[1, $first], [2, $second], [4, $third], [8, $fourth]];
            foreach ($payload as $data) {
                $sortargs(...$data);
            }
            $this->sort = end($args);
            if ($this->sort === $first) {
                return $this->load();
            }
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


    private function findUser($arg)
    {
        if (is_numeric($arg)) {
            $user = $this->usertable->find('id', $arg)[0];
            return $user->getDetails();
        } else {
            $user = $this->usertable->getEntity();
            return $user->fromDomain($arg, \PDO::FETCH_ASSOC);
        }
    }

    private function found($user_id, $text, $ext)
    {
        if (!isset($_SESSION['username'])) {
            reLocate(REG);
        }
        $srch = 0;
        $user = $this->usertable->find('email', $_SESSION['username'])[0];
        $details = $user->getDetails();
        $priv = $details['role'];
        $file = $this->table->getEntity();
        $pos = curry2('strpos');

        $cb = $this->validateFile($priv, $details['client_id'], $user->id);
        $files = [];
        $getExt = composer('strtolower', curry2('substr')(1), curry2('strrchr')('.'));
        $records = $this->table->findAll(null, 0, 0, \PDO::FETCH_ASSOC);
        $count = count($records);
        if ($user_id) {
            $details = $this->findUser($user_id);

            //  dump($user_id, $details);
            if ($priv == 'Admin') {
                if (!empty($details['client_id']) || !empty($details['domain'])) {
                    $records = toObject($file->getClientFiles($user_id), true);
                } else {
                    $records = $this->table->find('userid', $user_id, null, 0, 0, \PDO::FETCH_ASSOC);
                    $records = $records ?? [];
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
            $find = composer(negate('identity'), $contains, $getExt, curry2('getter')('filename'));
            if ($ext === 'owt') {
                $records = safeFilter($records, $find);
            } else {
                $eq = partial('equals', $ext);
                $byExt = composer($eq, $sub, $pos, curry2('getter')('filename'));
                $records = safeFilter($records, $byExt);
            }
        }
        //reset sort to default if filtering by any criteria
        if (count($records) !== $count) {
            $this->sort = 'tt';
        }
        //do we allow for filtering by user type
        $files = $this->prepFileForDisplay($records, $cb);

        if ($user_id) {
            $srch += 1;
        }
        if ($text) {
            $srch += 2;
        }
        if ($ext) {
            $srch += 4;
        }
        $srch += 8; //sort
        $setcookie = doSetCookie(true);
        $setcookie('searched', $srch);
        $this->pages = $this->setPages(count($files));
        $displayFiles = array_slice(toObject($files, true), $this->start, $this->display);
        return $this->displayer($user->id, $priv, $displayFiles, 'Clear Search Results', [], ['user_id' => $user_id, 'text' => $text, 'ext' => $ext]);
    }
}
