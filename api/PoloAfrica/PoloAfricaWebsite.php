<?php

namespace PoloAfrica;

use \Ninja\Website;
use \Ninja\DatabaseTable;
use \Ninja\Authentication;
use \PoloAfrica\Controllers\Pages;
use stdClass;

class PoloAfricaWebsite implements Website
{
    private $userTable;
    private $roleTable;
    private $userRoleTable;
    private $clientTable;
    private $uploadTable;
    private $pdo;
    private $authentication;
    private $home = '';

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
        include CONNECT;
        $this->pdo = $pdo;

        $this->userRoleTable = new DatabaseTable($this->pdo, 'userrole', 'userid');
        $this->roleTable = new DatabaseTable($this->pdo, 'role', 'id');
        $this->clientTable = new DatabaseTable($this->pdo, 'client', 'id');
        $this->userTable = new DatabaseTable($this->pdo, 'usr', 'id', '\PoloAfrica\Entity\User', [&$this->userTable, $this->clientTable, $this->userRoleTable, $this->roleTable]);
        $this->uploadTable = new DatabaseTable($this->pdo, 'upload', 'id', '\PoloAfrica\Entity\Uploader', [&$this->uploadTable, $this->userTable]);
        $this->authentication = new Authentication($this->userTable, 'email', 'password');
        //$this->authentication = new \stdClass();
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
        $eq = function ($a, $b) {
            return $a === $b;
        };
        $eq = fn($a, $b) => $a === $b;
        $arr = array_map($f, [ASSET_LOAD]);
        //if at least one matches
        return array_filter($arr, partial($eq, $f($uri)));
    }

    private function factory(string $classname, array $args)
    {
        $controllers = [
            'user',
            'login',
            'bolt',
            'uploader',
            'spadger'
        ];
        //https://stackoverflow.com/questions/534159/instantiate-a-class-from-a-variable-in-php#:~:text=Put%20the%20classname%20into%20a,%24classname(%22xyz%22)%3B
        $key = $this->validate($classname, $controllers);
        if ($key) {
            $klas = "PoloAfrica\\Controllers\\" . ucwords($key);
            return new $klas(...$args);
        }
    }

    private function build(string $name, array $mandatory, array $optional, array $user)
    {
        $classname = array_pop($user) ?? $name;
        $classname = ($classname === $name) ? $classname : $name;
        try {
            $class = $this->factory($classname, [...$mandatory, ...$optional, ...$user]);
        } catch (\Exception $e) {
            dump($e);
        }
        return $class;
    }

    private function ensureArray($arr)
    {
        return is_array($arr) ? $arr : [];
    }

    public function setNavBar(): array
    {
        return [[], []];
    }

    public function getController(string $name = '', array $args = [], array $user_args = []): ?object
    {
        $defaultArgs = [
            'logger' => [$this->authentication],
            'user' => [$this->userTable],
            'bolt' => [$this->authentication],
            'uploader' => [$this->uploadTable, $this->userTable],
            'spadger' => []
        ];

        if (isset($defaultArgs[$name])) {
            $args = $this->ensureArray($args);
            $user_args = $this->ensureArray($user_args);
            return $this->build($name, $defaultArgs[$name], $args, $user_args);
        }
        return null;
    }

    public function getScripts($key = ''): array
    {
        return [];
    }

    public function getControllerArgs($k): array
    {
        $lib = ['uploader' => [PAGINATE, 0, 1]];
        return $lib[$k] ?? [];
    }

    public function getLayoutVariables($key): array
    {
        /*
       $user = $this->authentication->isLoggedIn();
        if ($key === 'login') {
            return ['title' => 'Admin', 'loggedIn' => $user, 'user' => $user->name ?? ''];
        }
            */
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
        /*
       $files = scandir(isDir(ASSETS));
        $fs = preg_grep("/^\w+\.w+$/", $files);
        $dirs = arrayDiff($files, $fs);
        $dirs = array_values(preg_grep("/^[^\.]/", $dirs));

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
       */
        // $reroute = partial([$this, 'reroute'], $uri);
        $key = '';
        /*
        $browser = \PoloAfrica\Entity\User::BROWSER;
        $content = \PoloAfrica\Entity\User::CONTENT_EDITOR;
        $chief = \PoloAfrica\Entity\User::CHIEF_EDITOR;
        $account = \PoloAfrica\Entity\User::ACCOUNT_EDITOR;
        $super = \PoloAfrica\Entity\User::SUPERADMIN;
        */
        // $user = $this->authentication->isLoggedIn();
        //$permit = $user ? intval($user->permissions) : 0;
        $permit = 0;
        //$user = new \stdClass;
        $user = $this->authentication->isLoggedIn();
        /*
        $tmp = ['user/edit' => $account,  'user/list' => $account, 'user/edit' => $account];
        $post_access = ['user/success' => $browser, 'user/haspermission' => $browser];
        //'user/register' => $browser,
        $actions = [
        
        ];
        */
        function set($i = 0){
            return array_slice(['Browser', 'Manager', 'Client', 'Client Admin', 'Admin'], $i);
        }
       //$default = ['Admin', 'Client Admin', 'Client', 'Manager', 'Browser'];
        $actions = ['uploader/load' => set(), 'uploader/upload' => set(), 'uploader/update' => set(1), 'uploader/swap' => set(1), 'uploader/nav' => set(), 'uploader/delete' => set(1), 'uploader/confirm' => set(1), 'uploader/destroy' => set(1)];

        if (!$user) { //not logged in
            if ($this->baseAccess($uri) || isset($actions[$uri])) {
                reLocate(REG);
            }
        } else {
            $permit = isset($actions[$uri]) && $user->hasPermission($actions[$uri]);
            if (isset($actions[$uri]) && !$permit) {
                //$reroute($actions[$uri], 'user');
                exit;
            }
        }
        $ret = $user ? [$user, $permit, $key] : [''];
        //don't send empty args
        return array_filter($ret, 'identity');
    }
    //DDL
    public function create($name): void {}

    public function drop($name) {}
}
