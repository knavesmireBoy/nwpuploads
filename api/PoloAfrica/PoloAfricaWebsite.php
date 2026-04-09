<?php

namespace PoloAfrica;

use \Ninja\Website;
use \Ninja\DatabaseTable;
use \Ninja\Authentication;
use \PoloAfrica\Controllers\Pages;

class PoloAfricaWebsite implements Website
{
    private $articleTable;
    private $userTable;
    private $assetTable;
    private $slotTable;
    private $boxTable;
    private $galleryTable;
    private $pdo;
    private $pagesTable;
    private $authentication;
    private $pages;
    private $pp;
    private $home = 'home';

    public function getDefaultRoute(): string
    {
        return $this->home;
    }

    public function setHome($str): string
    {
        $this->home = $str;
        return $str;
    }
    public function __construct($pp)
    {
        $pwd = 'covid19krauq';
        $user = 'root';
        $dbname = 'uploads';
        //$host = $_ENV['MYSQL_DATABASE'];
        /*emoji
        CREATE DATABASE polafrica DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;
        ALTER DATABASE polafrica CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
        CREATE TABLE IF NOT EXISTS articles (...) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
        ALTER TABLE `articles` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
        SET NAMES utf8mb4; 
        ALTER DATABASE `articles` CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci;
        ALTER TABLE articles MODIFY content TEXT, CHARSET utf8mb4;
        SHOW VARIABLES WHERE Variable_name LIKE 'character\_set\_%' OR Variable_name LIKE 'collation%';
        */
        $this->pdo = new \PDO(
            "mysql:host=localhost;dbname=polafrica;charset=utf8mb4",
            $user,
            $pwd
        );

        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('SET NAMES "utf8"');
        $this->userTable = new DatabaseTable($this->pdo, 'user', 'id', '\PoloAfrica\Entity\User', [&$this->userTable]);
        $this->authentication = new Authentication($this->userTable, 'email', 'password');
        $this->pagesTable = new DatabaseTable($this->pdo, 'pages', 'id', '\PoloAfrica\Entity\Page', [&$this->slotTable]);
        $this->slotTable = new DatabaseTable($this->pdo, $pp, 'id', '\PoloAfrica\Entity\Slot', [&$this->slotTable]);
        $this->assetTable = new DatabaseTable($this->pdo, 'assets', 'id', '\PoloAfrica\Entity\Asset', [&$this->assetTable, &$this->articleTable]);
        $this->articleTable = new DatabaseTable($this->pdo, 'articles', 'id', '\PoloAfrica\Entity\Article', [&$this->articleTable, $this->assetTable, $this->slotTable, 2]);
        $this->boxTable = new DatabaseTable($this->pdo, 'slot', 'id');
        $this->galleryTable = new DatabaseTable($this->pdo, 'gallery', 'id', '\PoloAfrica\Entity\Gallery', [$this->boxTable]);
        //  $this->pages = array_map(fn($o) => strtolower($o->name), $this->pagesTable->findAll());
    }

    private function validate($key, $array)
    {
        $k = ($key === 'logger') ? 'login' : $key;
        return in_array($k, $array) ? $k : null;
    }
    //normalise strings by removing forward slashes
    private function baseAccess($uri)
    {
        $f = partial('preg_replace', '|\/|', '');
        // $eq = fn($a, $b) => $a === $b;
        $eq = function ($a, $b) {
            return $a === $b;
        };
        $arr = array_map($f, [BADMINTON, USER_LIST]);
        //if at least one matches
        return array_filter($arr, partial($eq, $f($uri)));
    }


    private function factory(string $id, array $args)
    {
        $controllers = [
            'article',
            'asset',
            'user',
            'login',
            'gallery',
            'pages',
            'contact',
            'bolt',
            'spadger'
        ];
        //https://stackoverflow.com/questions/534159/instantiate-a-class-from-a-variable-in-php#:~:text=Put%20the%20classname%20into%20a,%24classname(%22xyz%22)%3B
        $key = $this->validate($id, $controllers);
        if ($key) {
            $klas = "PoloAfrica\\Controllers\\" . ucwords($key);
            return new $klas(...$args);
        }
    }

    private function build(string $name, array $mandatory, array $optional, array $user)
    {
        $id = array_pop($user) ?? $name;
        $id = ($id === $name) ? $id : $name;
         return $this->factory($id,[],/* [...$mandatory, ...$optional, ...$user]*/);
    }

    private function ensureArray($arr)
    {
        return is_array($arr) ? $arr : [];
    }

    //recur
    private function list($pagedata, $pagenames, $ret, $i, $j)
    {
        if (isset($pagedata[$i]) && isset($pagenames[$j])) {
            $tgt = $pagenames[$j];
            //iterate until you find KEY to title
            if ($pagedata[$i]['name'] === $tgt) {
                $ret[] = $pagedata[$i]['title'];
                $j += 1; //advance
                $i = 0; //reset
                return $this->list($pagedata, $pagenames, $ret, $i, $j);
            } else {
                //increment $pagedata
                $ret = $this->list($pagedata, $pagenames, $ret, $i += 1, $j);
                return $ret;
            }
        } else {
            return $ret;
        }
    }

    public function setNavBar(): array
    {
        $pagedata = $this->pagesTable->findAll(null, 0, 0, \PDO::FETCH_ASSOC);
        $e = $this->pagesTable->getEntity();
        $e->setName('pp');
        $pagenames = array_map(function ($arr) {
            return $arr['title'];
        }, $e->findAll('id', 0, 0, \PDO::FETCH_ASSOC));
        /*assumes $pagedata and $pagenames are same length!;
        $pagenames is effectively generated by $pagedata
        as a reminder page titles can be different to page names (which are effectively ids)
        */
        $pagetitles = $this->list($pagedata, $pagenames, [], 0, 0);
        /*
        we could easily dispense with this->list by using some sql...
        but list may prove handy in other scenarios
        $res = $this->pagesTable->orderBySlot('.title', 'pp', '.name', '.title');
        $pagetitles = array_column($res, 'title'); 
        */
        $a = [];
        $b = [];
        //show menu item conditional on active articles
        foreach ($pagenames as $k => $v) {
            $tmp = $this->articleTable->find('page', $v);
            if ($tmp) {
                $a[] = $v;
                $b[] = $pagetitles[$k];
            }
        }
        return [$pagenames, $pagetitles];
        return [$a, $b];
    }

    public function getController(string $name = '', array $args = [], array $user_args = []): ?object
    {
        $defaultArgs = [
            'logger' => [$this->authentication],
            'user' => [$this->userTable],
            'article' => [$this->articleTable],
            'asset' => [$this->assetTable],
            'gallery' => [$this->galleryTable, $this->boxTable],
            'contact' => [],
            'bolt' => [],
            'spadger' => []
        ];

        $locations = ['photos' => GAL_LIST, '_enquiries' => 'processForm'];


        if (isset($defaultArgs[$name])) {
            $args = $this->ensureArray($args);
            $user_args = $this->ensureArray($user_args);
            return $this->build($name, $defaultArgs[$name], $args, $user_args);
        }
        // return new Pages($this->pagesTable, $this->articleTable, $this, $name, $locations);
    }

    public function getScripts($key = ''): array
    {
        //note mis-spelling scripts results in: SyntaxError: Unexpected token '<'
        /*
        issue with ajax and cancel &cancel=cancel, need to find a way to determine
        when form submission was cancelled Probably click handler until then we prevent ajax
        on forms with a cancel option which means we have to reload JS for some routes
        UPDATE  dispense with cancel button, but, ensure we have a back button.
        */
        $js = ['viewport', 'meta', 'utils'];
        $admin = array_merge($js, ['present', 'admin']);
        $markup = array_merge($js, ['present', 'markup', 'admin']);
        $gallery = array_merge($js, ['iterator', 'tooltips', 'publisher', 'painter', 'slideshow', 'present', 'gallery'/*, 'cacher'*/]);
        $scripts =  [
            'user/admin' => $markup,
            'logger/reg' => $admin,
            'logger/login' => $admin,
            'logger/loginSubmit' => $admin,
            'user/list' => $admin,
            'article/list' => $markup,
            'article/edit' => $markup,
            'article/assets' => $admin,
            'pages/list' => $admin,
            'gallery/review' => $admin,
            'gallery/display' => $gallery,
            //below three gallery/x lines would only be neccesary if JS was enabled AFTER a selection was made from the gallery
            'gallery/loadpic' => $gallery,
            'gallery/next' => $gallery,
            'gallery/prev' => $gallery,
            //..  'contact/process' => [...$js, 'present', 'ajax'],
            //..  'home/display' => [...$js, 'homealone', 'present', 'ajax']
        ];
        return [];
        // return $scripts[$key] ?? (strpos($key, 'display') ?  [...$js, 'present', 'ajax'] : []);
    }

    public function getControllerArgs($k): array
    {
        $gallery_map = [[14, 0], [14, 14], [14, 28], [12, 42], [12, 54], [12, 66], [14, 78]];
        $accept_asset = 'accept="image/*, video/*,application/pdf"';
        $gallery_accept = 'accept="image/*"';
        $loader_args = ['application' => [ASSETS], 'image' => [ARTICLE_IMG, ARTICLE_THUMB], 'video' => [VIDEOS]];
        $gallery_args = ['image' => [GALLERY_IMG, GALLERY_THUMBS, 1.5]];
        $lib = [
            'gallery' => [$gallery_map, $gallery_accept, $gallery_args],
            'article' => [10, [5, 12]],
            'asset' => [$accept_asset, $loader_args]
        ];
        return isset($lib[$k]) ? $lib[$k] : [];
    }

    public function getLayoutVariables($key): array
    {
        $user = $this->authentication->isLoggedIn();
        if ($key === 'login') {
            return ['title' => 'Admin', 'loggedIn' => $user, 'user' => $user->name ?? ''];
        }
        $page = explode('/', $key);
        $gal = 'gallery';
        $defs = ['klas' => '', 'user' => $user->name ?? '', 'adminpage' => ''];
        $pp = ['adminpage' => true];
        $lookup = [
            /*
            'user/register' => ['title' => 'Admin', ...$defs],
            'article/list' => ['title' => 'Admin', ...$defs, ...$pp],
            'pages/list' => ['title' => 'Admin', ...$defs, ...$pp],
            'gallery/display' => ['title' => 'photos', ...$defs, 'klas' => 'public'],
            'gallery/nextpage' => ['title' => 'photos', ...$defs],
            'gallery/prevpage' => ['title' => 'photos', ...$defs],
            'gallery/loadpic' => ['title' => 'photos', 'klas' => 'showtime'],
            'gallery/next' => ['title' => 'photos', 'klas' => 'showtime'],
            'gallery/prev' => ['title' => 'photos', 'klas' => 'showtime'],
            'contact/process' => ['title' => 'Enquiries',  ...$defs, 'klas' => 'public']
            */];

        if ($page[0] === 'gallery') {
            if (empty($lookup[$key])) {
                $gal = '';
            }
        }
        //./
        //.. $klas = in_array($page[0], [...$this->pages, $gal]) ? 'public' : '';
        $klas = 'Admin';
        $title = $klas ? $page[0] : 'Admin';
        return isset($lookup[$key]) ? $lookup[$key] : ['title' => $title, 'klas' => $klas, 'user' => $user->name ?? '', 'adminpage' => !$klas];
    }
    //needs to be public method because use of partial 1st line of checkLogin which uses call_user_func_array
    public function reroute($uri, int $acceslevel, string $flag = '')
    {
        $route = explode('/', $uri);
        $name = $flag ? $flag : $route[0];
        $action = $route[1];
        //$acceslevel will determine the feedback message supplied to acccessdenied.html.php
        $args = "!$action/$acceslevel";
        //CRUCIAL set $route to lowercase otherwise it falls foul of EntryPoint::checkUri
        $route = strtolower($name . '/message/' . $args);
        reLocate("/$route", '../');
    }

    public function checkLogin(string $uri): array
    {
        $files = scandir(isDir(ASSETS));
        $fs = preg_grep("/^\w+\.w+$/", $files);
        $dirs = arrayDiff($files, $fs);
        $dirs = array_values(preg_grep("/^[^\.]/", $dirs));

        // dump(tsb(2400, 14250, 9, .2));

        function foo($root, &$ret)
        {
            $files = safeScanDir($root);
            $drive = function ($dirname, $i) use ($root, $files, $ret, &$drive) {
                if (!isset($root[$i])) {
                    return $ret;
                }
                if (!$dirname) {
                    $sub = isDir($root . $files[$i]);
                    if ($sub) {
                        return $drive($files[$i], $i);
                    } else {
                        $ret[] = $files[$i];
                    }
                } else {
                    $sub = isDir($root . $dirname);
                    $subfiles = safeScanDir($sub);
                    // var_dump($sub);
                    $j = 0;
                    while ($subfiles[$j]) {
                        $ret[] = $subfiles[$j];
                        $j++;
                    }
                }
                return $drive('', $i += 1);
            };
            return $drive;
        }

        $reroute = partial([$this, 'reroute'], $uri);
        $user = $this->authentication->isLoggedIn();
        $key = '';
        $browser = \PoloAfrica\Entity\User::BROWSER;
        $content = \PoloAfrica\Entity\User::CONTENT_EDITOR;
        $photo = \PoloAfrica\Entity\User::PHOTO_EDITOR;
        $chief = \PoloAfrica\Entity\User::CHIEF_EDITOR;
        $account = \PoloAfrica\Entity\User::ACCOUNT_EDITOR;
        $super = \PoloAfrica\Entity\User::SUPERADMIN;

        $permit = $user ? intval($user->permissions) : 0;
        $tmp = ['user/edit' => $account,  'user/list' => $account, 'user/edit' => $account, 'gallery/manage' => $photo];
        $post_access = ['user/success' => $browser, 'user/haspermission' => $browser];
        //'user/register' => $browser,
        $actions = [
            'user/confirm' => $account,
            'user/permissions' => $account,
            'user/changepassword' => $browser,
            'user/changeemail' => $browser,
            'user/forgot' =>  $browser,
            'article/list' => $content,
            'article/edit' => $content,
            'article/confirm' => $content,
            'article/delete' => $content,
            'article/move' => $content,
            'article/restore' => $content,
            'article/assets' => $content,
            'asset/upload' => $content,
            'asset/delete' => $content,
            'asset/edit' => $content,
            'asset/confirm' => $content,
            'asset/assign' => $content,
            'asset/reload' => $content,

            'asset/add' => $super,

            'pages/list' => $content,
            'pages/edit' => $content,
            'gallery/review' => $photo,
            'gallery/add' => $photo,
            'gallery/upload' => $photo,
            'gallery/edit' => $photo,
            'gallery/destroy' => $photo,
            'gallery/reload' => $photo,
            'gallery/assign' => $photo,
            'pages/add' => $content,
            'pages/delete' => $chief,
            'pages/confirm' => $chief,
            'pages/approve' => $chief,

            'asset/manage' => $super,
            'asset/retrieve' => $super,
            'asset/getuntracked' => $super,
            'gallery/retrieve' => $super,
            'gallery/getuntracked' => $super,
            'gallery/manage' => $super,
        ];
        //browser is simply a registered user who has access to privileged PUBLIC content
        //for instance slideshow
        //$actions = $this->restrictGalleryAccess($actions);
        if (!$user) { //not logged in
            //@ baseAccess
            //a non-browser has to be able to register user/admin
            //a "BROWSER" is allowed to change details at the very least user/list
            if ($this->baseAccess($uri) || isset($actions[$uri])) {
                reLocate(REG . 'gebruiker');
            }
        } else {
            if (isset($actions[$uri]) && !$user->hasPermission($actions[$uri])) {
                $reroute($actions[$uri], 'user');
                exit;
            }
        }
        $ret = $user ? [$user, $permit, $key] : [''];
        //don't send empty args
        return [''];
        return array_filter($ret, 'identity');
    }
    //DDL
    public function create($name): void
    {
        //COLLATE=utf8mb3_general_ci
        //=utf8mb4_unicode_ci
        try {
            $fk = $name . '_fk';
            $ix = $name . '_ix';
            $sql = "CREATE TABLE IF NOT EXISTS $name (
                `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
                `title` varchar(50) NOT NULL,
                PRIMARY KEY (`id`,`title`),
                CONSTRAINT `$fk` FOREIGN KEY (`title`) REFERENCES `articles` (`title`) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci";
            $this->pdo->exec($sql);
            unset($_SESSION['nav']);
            reLocate(PAGES_LIST);
        } catch (\PDOException $e) {
            $output = 'Error creating table: ' . $e->getMessage();
            exit();
            reLocate(BBC);
        }
    }

    public function drop($name)
    {
        // dump($this->slotTable->findAll(null, 1));
        try {
            $sql = "DROP TABLE IF EXISTS $name";
            $this->pdo->exec($sql);
            $all = $this->pagesTable->findAll();
            $i = count($all);
            $sql = "ALTER TABLE pages AUTO_INCREMENT = $i";
            $this->pdo->exec($sql);
            return $name;
        } catch (\PDOException $e) {
            $output = 'Error deleting table: ' . $e->getMessage();
            exit();
        }
        if ($name === $this->home) {
        }
    }
}
