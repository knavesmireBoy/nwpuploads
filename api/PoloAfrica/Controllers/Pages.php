<?php
/*
HEADS UP
strtolower is used a fair bit here
ONLY BECAUSE using caps for the NAME of the page in the pages table..
id | name | title
10 | TOM | Our Tom
11 | dick | Our Dick
MENU: [TOM][Our Dick]
..is an indication to use the name and not the title in the navigation menu
BUT
we need to avoid using caps in the REQUEST_URI see entrypoint

RAN INTO BOTHER WHEN SOME FOREIGN KEY CONSTRAINTS DID NOT SURVIVE A DUMP
DELETE PAGES AND START AGAIN
*/

namespace PoloAfrica\Controllers;

include_once 'config.php';

use \Ninja\DatabaseTable;
use \Ninja\Strategy\Checker;
use \Ninja\Strategy\Negator;
use \Ninja\Strategy\Empti;
use \Ninja\Strategy\PhoneNumber;
use \Ninja\Strategy\isName;
use \Ninja\Strategy\isEmail;
use \Ninja\Strategy\isMatch;
use \Ninja\Strategy\isSmallMsg;
use \Ninja\Strategy\isLargeMsg;
use \Ninja\Composite\Composite;

use \Michelf\MarkdownExtra;

class Pages extends Composite
{
    private $pp = '';

    public function __construct(private DatabaseTable $table, private DatabaseTable $article_table, private \PoloAfrica\PoloAfricaWebsite $website, string $pp, private array $locations)
    {
        $this->validatePage(strtolower($pp));
    }

    protected function persist($record, $route = 'edit')
    {
        $unset = doSetCookie(false);
        $set = doSetCookie(true);
        try {
            $unset('error');
            $e = $this->table->save($record);
        } catch (\Exception $e) {
            $msg = 'Error saving record: ' . $e->getMessage();
            $set('error', $msg);
            reLocate(PAGES_EDIT . $record['id'] ?? '');
        }
        return $e;
    }
    private function inArray($needle, $haystack)
    {
        $cb = function ($n) {
            return function ($agg, $cur) use ($n) {
                return $agg ? $agg : preg_match("/^$n$/i", $cur);
            };
        };
        return array_reduce($haystack, $cb($needle));
    }

    private function hasTitle($content)
    {
        $hardcoded = partial('preg_match', '/^\w+\.html\.php$/');
        return $hardcoded($content) || (!empty($content) && preg_match('/^#/', $content));
    }

    public function redirect()
    {
        $page = $this->locations[$this->pp] ?? '';
        if ($page) {
            return partial('reLocate', $page);
        }
        return $this->locations["_$this->pp"] ?? '';
    }

    private function sortPairs($k, $v)
    {
        $id = preg_match('/photos/i', $v) ? "/gallery/display" : "/$k/";
        $id = "/$k/";
        $val = ($k === strtoupper($k)) ? strtolower($k) : $v;
        return [strtolower($id), ucwords($val)];
    }

    private function repop($pp, $rmv, $add = '')
    {
        $currentList = array_map(fn($o) => $o->title, $pp->findAll());
        if ($add) $currentList[] = $add;
        $currentList = array_map('trim', $currentList);
        $currentList = safeFilter($currentList, fn($str) => $str !== $rmv);
        return $pp->repop($currentList);
    }

    private function updateNavBar($a, $b)
    {
        $op = 'add';
        if (count($a) >= count($b)) {
            $item = array_diff($a, $b);
        } else {
            $item = array_diff($b, $a);
            $op = 'remove';
        }
        if (!empty($item)) {
            $item = array_values($item)[0];
        }
        if ($item) {
            if ($op === 'add') {
                $a = $b;
                $a[] = $item;
                $_SESSION['navbar'] = $a;
            } else {
                $_SESSION['navbar'] = array_filter($b, fn($o) => $o !== $item);
            }
        }
    }

    protected function validatePage($pp)
    {
        $entity = $this->table->getEntity();
        $entity->setName('pp');
        $pages = $entity->findAll('id');
        $pages = array_map(fn($o) => $o->title, $pages);
        $pass = $this->inArray($pp, [...$pages, 'pages']);
        /*
        if (empty($_SESSION['navbar'])) {
            //maintain order of pages on new session
            $_SESSION['navbar'] = $pages;
        }
        */
        if (!$pass && $pp === 'home' && isset($pages[0])) {
            $pp = $pages[0];
            $this->pp = $pp;
            retour($pp);
        } else if (!$pass) {
            reLocate("/");
        }
        $this->pp = $pp;
    }

    protected function validate($pp, $data)
    {
        $check = count($this->article_table->find('page', $pp));
        $passed = count(array_filter($data, fn($o) => $o));
        return $check === $passed;
    }

    protected function getAccess($i)
    {
        //2 'Content Editors' //4 'Photo Editors' 
        $lib = [1 => 'Registered Users', 2 => 'Content Editors', 4 => 'Photo Editors', 8 => 'Chief Editors'];
        return isset($lib[$i]) ? $lib[$i] : 'Account Administrators';
    }

    protected function fetch($t, $prop, $val, ...$rest)
    {
        $ret = [];
        if ($val) { //safeguard against missing values
            if (strtoupper($t) === $t) {
                $t = strtolower($t);
                $ret = $this->{$t}->find($prop, $val, null, 0, 0, \PDO::FETCH_ASSOC);
            } else {
                $ret = $this->{$t}->find($prop, $val, ...$rest);
            }
        }
        return empty($ret) ? null : $ret[0];
    }

    protected function order($pp)
    {
        if (isset($_POST['pk'])) {
            $label = $_POST['details']['name'];
            $destinationID = intval($_POST['position'] - 1);
            $shuffle = isset($_POST['shuffle']);
            $pp = $this->fetch('table', 'id', $_POST['pk']);
            $pp->setName('pp');
            if ($pp) {
                if ($shuffle) {
                    $res = $pp->shuffle($destinationID, $label);
                } else {
                    $res = $pp->swap($destinationID, $label);
                }
            }
            if ($res) {
                unset($_SESSION['nav']);
                unset($_SESSION['navbar']);
                $this->setNavBar();
                retour();
            }
        }
        reLocate(BADMINTON, '../..');
    }

    private function fetchAll($entity, $table, $k)
    {
        $all = $entity->findAll('id');
        $items = [];
        $last = null;


        foreach ($all as $item) {
            if ($item) {
                $last = $this->fetch($table, $k, $item->title);
                if ($last) {
                    if (!$this->hasTitle($last->content)) {
                        continue;
                    }
                    $items[] = $last;
                }
            }
        }
        return $items;
    }

    private function getPopulatedPages($navlist)
    {
        $ret = [];
        $active = array_unique(array_map(fn($o) => $o['page'], $this->article_table->filterNull('page', false, 'id', 0, 0, \PDO::FETCH_ASSOC)));
        foreach ($navlist as $n) {
            if (in_array($n, $active)) {
                $ret[] = $n;
            }
        }
        return $ret;
    }

    private function getDefaultPage($str)
    {
        // if (preg_match('/!$/', $str)) {
        $title = $this->table->orderBySlot('.title', 'pp', '.name', '.title')[0]['title'];
        $page = $this->fetch('table', 'title', $title);
        return $page->name;
        //}
    }

    protected function getPages($id = 0)
    {
        $flag = false;
        $all = $this->table->findAll('id', 0, 0, \PDO::FETCH_ASSOC);
        $pp = $this->table->getEntity();
        $pp->setName('pp');
        $res = $id ? $pp->find($id) : $pp->findAll('id');
        if (empty($res)) { //pp emptied
            if ($id) {
                $res = array_filter($all, fn($o) => $o['id'] == $id)[0];
                $flag = true;
            } else {
                $res = array_map(fn($o) => $o['name'], $all);
            }
        }
        return [$res, $flag];
    }

    protected function checkArticles($entity)
    {
        $cb = fn($o) => $o->title;
        $displayOrder = $this->fetchAll($entity, 'article_table', 'title');

        $currentArticles = array_map($cb, $this->article_table->find('page', $this->pp));
        list($diff) = getDiff($currentArticles, array_map($cb, $displayOrder));

        if (!empty($diff)) { //NEW article to be appended
            $entity->trigger($diff);
        }
    }

    protected function fetchArticles()
    {
        $entity = $this->article_table->getEntity();
        $entity->setName($this->pp);
        $this->checkArticles($entity);
        return $this->fetchAll($entity, 'article_table', 'title');
    }

    protected function buildArticles()
    {
        $articles = $this->fetchArticles();
        $comp = null;
        $id = null;
        $articles = array_map(function ($article) {
            $article->mdcontent = MarkdownExtra::defaultTransform($article->content);
            return $article;
        }, $articles);

        foreach ($articles as $leaf) {
            $leaf->assets = $leaf->getAssets($leaf->id);
            $res = findMatch('|(\w*)\.section|', $leaf->attr_id, 1);
            if ($res) {
                if ($id && $res !== $id) {//ensure $leafs belong to the correct section
                    if ($comp) $this->addItem($comp);
                    $comp = new Composite();
                    $comp->addItem($leaf);
                } else {
                    $id = $res;
                    $comp = $comp ? $comp : new Composite();
                    $comp->addItem($leaf);
                }
            } else {
                if (!empty($comp)) {
                    $this->addItem($comp);
                    $comp = null;
                }
                $this->addItem($leaf);
            }
        }
        //if there were no further articles after a .section article, $this->addItem($comp) would not get called in the loop
        if ($comp) {
            $this->addItem($comp);
        }
    }

    private function getTemplate($pp = null, $key = '')
    {
        $max = count($this->table->findAll());
        return [
            'template' => 'editpage.html.php',
            'title' => "Edit Page",
            'variables' => [
                'action' => PAGES_EDIT,
                'exit' => BADMINTON,
                'ppid' => $pp->id ?? 0,
                'pptitle' => $pp->title ?? '',
                'ppname' => $pp->name ?? '',
                'ppmax' => $pp ? $max : 0,
                'ppcontent' => $pp->content ?? '',
                'ppdescription' => $pp->description ?? '',
                'required' => '',
                'key' => $key,
                'submit' => 'submit'
            ]
        ];
    }

    protected function processForm()
    {
        $host = 'north.wolds@btinternet.com';
        $to = 'andrewsykes@btinternet.com';
        $expected = array(
            'name',
            'email',
            'msg',
            'phone',
            'addr1',
            'addr2',
            'addr3',
            'addr4',
            'postcode',
            'country',
            'comments'
        );
        $text = "Please use this area for comments or questions";
        $post_text = 'Please enter your message';
        $klas = 'empty';
        $mailsent = false;
        $missing = [];
        $mailnotsent = '';
        $fieldset = 'Poloafrica contact form';
        $suspect = false;
        $pairs = array(
            'phone' => 'email'
        );
        $item = 'item';
        $empty = new Checker("required fields are indicated", new Negator(new Empti()));
        $subtext = substr($text, 0, 13);
        $subpost_text = substr($post_text, 0, 13);
        $isNum = new Checker('please supply a phone number', new PhoneNumber());
        $isEmail = new Checker('please supply a valid email address', new isEmail());
        $isName = new Checker('please supply name in the expected format: "FirstName [MiddleName] LastName"', new isName());
        $isSmallMsg = new Checker('Message is very small, please elaborate', new isSmallMsg());
        $isLargeMsg = new Checker('Word count of your message is too great. Reduce word count or please email instead', new isLargeMsg());
        $comment = new Checker($post_text, new Negator(new isMatch("/^$subtext/")));
        $postcomment = new Checker($post_text, new Negator(new isMatch("/^$subpost_text/")));
        $required = array(
            'name' => preconditions($empty, $isName),
            'email' => preconditions($empty, $isEmail),
            'comments' => preconditions($empty, $comment, $postcomment, $isSmallMsg, $isLargeMsg)
        );

        if (!empty($_POST['details'])) {
            $message = '';
            $data = array_map('spam_scrubber', $_POST['details']);
            $suspect = !empty(array_filter($data, 'single_space'));
            //honeypot
            if (!$suspect && $_POST['url']) {
                $suspect = true;
            }
            if (!$suspect) {
                foreach ($data as $k => $v) {
                    if (isset($required[$k])) {
                        $res = $required[$k]('identity', $v);
                        //$res will be a string if valid, or an array of issues
                        if (is_array($res)) {
                            $missing[$k] = $res;
                            $k = null;
                        }
                    }
                    if (in_array($k, $expected)) {
                        //sets vars used below, $email, $comments
                        ${$k} = trim($v);
                        $message .= buildMessage($k, $v, $k === 'comments');
                    }
                } //each
            }
            if (empty($missing)) {
                $message = wordwrap($message, 70);
                $headers = "From: $host";
                $headers .= "\r\nContent-Type: text/plain; charset=utf-8";
                $headers .= "\r\nReply-To: $email";
                //$mailsent = mail($host, 'Website Enquiry', $message, $headers);
                $mailsent = true;
                $klas = 'success';
                if ($mailsent) {
                    unset($missing);
                } else {
                    $mailnotsent = '<h1>Sorry, There was a problem sending your message. Please try again later.</h1>';
                } //not sent
            } //ok
            else {
                $item = count($missing) > 1 ? 'items' : 'item';
                $fieldset = "Please complete the missing $item indicated";
                $klas = 'warning ';
                //sort...
                $keys = array_keys($missing);
                $fieldset = array_values($missing)[0][0];
                $klas .= $keys[0];
                //https://stackoverflow.com/questions/24403817/html5-required-attribute-one-of-two-fields;
            }
        } //posted
        //NOTE variables created on-the-fly for $email(line 327) and $comments
        $this->buildArticles('enquiries');
        return [
            'template' => 'page.html.php',
            'title' => ucfirst('enquiries'),
            'variables' => [
                'klas' => $klas,
                'mailsent' => $mailsent,
                'mailnotsent' => $mailnotsent,
                'missing' => $missing ?? [],
                'fieldset' => $fieldset,
                'myemail' => $email ?? '',
                'name' => $data['name'] ?? '',
                'phone' => $data['phone'] ?? '',
                'myemail' => $data['email'] ?? '',
                'mycomments' => $data['comments'] ?? '',
                'country' => $data['country'] ?? '',
                'postcode' => $data['postcode'] ?? '',
                'addr1' => $data['addr1'] ?? '',
                'addr2' => $data['addr2'] ?? '',
                'addr3' => $data['addr3'] ?? '',
                'addr4' => $data['addr4'] ?? '',
                'email' => $email ?? 'andrewsykes@btinternet.com',
                'comments' => $comments ?? '',
                'articles' => $this->items
            ]
        ];
    }

    public function display()
    {
        $action = $this->redirect();
        if (is_callable($action)) {
            return $action();
        } else if (is_callable([$this, $action])) {
            return $this->{$action}();
        }
        $this->buildArticles();
        return [
            'template' => 'page.html.php',
            'title' => ucfirst($this->pp),
            'variables' => [
                'articles' => $this->items,
                'klas' => ''
            ]
        ];
    }
    public function getTitle($name)
    {
        $res = $this->fetch('table', 'name', $name);
        return $res->title;
    }

    public function getProp($col, $val, $prop = null)
    {
        $res = $this->fetch('table', $col, $val);
        return $prop ? $res->{$prop} : $res;
    }

    public function add($key = '')
    {
        $name = $_SESSION['pending'] ?? null;
        if (isset($name)) {
            reLocate(PAGES_APPROVE . strtolower($name));
        }
        return $this->getTemplate(null, $key);
    }

    public function edit($id = 0)
    {
        $name = $_SESSION['pending'] ?? null;
        if (isset($name)) {
            reLocate(PAGES_APPROVE . strtolower($name));
        }
        $pp = $this->fetch('table', 'id', $id);
        if ($id) {
            return $this->getTemplate($pp);
        }
        reLocate('/pages/add');
    }

    public function delete($id)
    {
        $file = $this->fetch('table', 'id', $id);
        return [
            'template' => 'delete.html.php',
            'title' => ucfirst($this->pp),
            'variables' => [
                'action' => "/pages/confirm/",
                'perform' => 'delete',
                'file' => $file,
                'submit' => 'submit'
            ]
        ];
    }

    public function createSubmit($str = '')
    {
        if (isset($_POST['cancel'])) {
            $this->table->delete('name', 'pending');
            unset($_SESSION['pending']);
        }
        $str = $_POST['pk'] ?? '';

        if ($str) {
            $s = strtolower($str);
            $res = $this->fetch('table', 'name', $s);
            if (!$res) {
                $res = $this->fetch('TABLE', 'name', 'pending');
                $res['name'] = $str;
                $this->persist($res);
                $this->website->create($s);
            }
        }
        reLocate(PAGES_LIST);
    }

    public function approve($str = '')
    {
        if (isset($_SESSION['pending'])) {
            $str = $_SESSION['pending'];
            unset($_SESSION['pending']);
            $s = strtolower($str);
            $para = "Please approve the creation of a new page: <strong>$s</strong>";
            return [
                'template' => 'delete.html.php', //provides cancel and submit buttons
                'title' => 'Approve New Page',
                'variables' => [
                    'action' => "/pages/create/",
                    'perform' => 'create',
                    'para' => $para,
                    'file' => toObject(['id' => $str]), //to conform with template
                    'submit' => 'submit',
                    'exit' => PAGES_LIST
                ]
            ];
           // exit;
        }
    }

    public function confirm()
    {
        /*ALTER TABLE articles ADD CONSTRAINT `pager_fk` FOREIGN KEY (`page`) REFERENCES `pages` (`name`) ON DELETE SET NULL ON UPDATE CASCADE;*/
        if (isset($_POST['cancel'])) {
            reLocate(PAGES_LIST);
        }
        $id = $_POST['pk'] ?? 0;
        $payload = $this->fetch('TABLE', 'id', $id);

        if ($payload) {
            $pp = $this->fetch('table', 'id', $id);
            $this->table->delete('id', $id);
            $this->website->drop(strtolower($pp->name));
            unset($_SESSION['nav']);
            //retour();
            reLocate(PAGES_LIST);
        }
    }
    public function editSubmit()
    {
        /*
        the general idea here is that a new page can be created BUT it MAY be depending approval
        by a senior member */
        $update = true;
        $payload = array_map('trim', $_POST['details']);

        $position = !empty($_POST['position']);
        $current = null;
        if (isset($_POST['pk'])) {
            $payload['id'] = $_POST['pk'];
            $current = $this->fetch('TABLE', 'id', $_POST['pk']);
            $payload['name'] = $current['name'];
            /*
            if (strtolower($payload['name']) === strtolower($current['name'])) {
                if (isUpperCase($payload['name'])) {
                    $current['name'] = $payload['name'];
                }
            } else {
                $payload['name'] = $current['name'];
            }
                */
            //!avoid changing name as there MAY be no slot db, delete and insert instead
        } else {
            $payload['name'] = 'pending';
            $update = false;
        }

        //save current page details
        $pp = $this->table->save($payload);
        $pp->setName('pp');
        if ($position) {
            //unset($_SESSION['nav']);
            $this->order($pp);
        } else {
            if (!$update) {
                // $name = trimToLower($_POST['details']['name']);
                //!$payload['name] which be 'pending'
                $name = trim($_POST['details']['name']); //!$payload['name] which be 'pending'
                $pending = $this->table->findAll('id', 0, 0, \PDO::FETCH_ASSOC);
                $names = array_map(fn($o) => $o['name'], $pending);
                //guard against inserting a page with existing name FCOL
                if (in_array($name, $names)) {
                    $this->table->delete('name', 'pending');
                    reLocate(PAGES_ADD . "/baddpage");
                }
                $this->repop($pp, '', 'pending');
                $_SESSION['pending'] = $name;
                reLocate("/pages/approve/");
            }
            reLocate(PAGES_LIST);
        }
    }

    public function setNavBar()
    {
        if (empty($_SESSION['nav'])) {
            $all = $this->table->findAll('id', 0, 0, \PDO::FETCH_ASSOC);
            $pp = $this->table->getEntity();
            $pp->setName('pp');
            $navlist = $this->getPopulatedPages(array_map(fn($o) => $o['name'], $all));

           
            $list = array_map(fn($o) => $o->title, $pp->findAll('id')); //current order
            if (empty($_SESSION['navbar'])) {
                $_SESSION['navbar'] = $list;
            } else if (count($list) !== count($_SESSION['navbar'])) {
                $this->updateNavBar($list, $_SESSION['navbar']);
            }
            $gang = orderByList($all, $_SESSION['navbar'], 'name', 'title');
            $assoc = [];
            $nav = [];
            foreach ($gang as $k => $v) {
                if (in_array($k, $navlist)) {
                    $assoc[$k] = $gang[$k];
                }
            }
            foreach ($assoc as $k => $v) {
                list($id, $val) = $this->sortPairs($k, $v);
                $nav[$id] = $val;
            }
            $_SESSION['nav'] = $nav;
        }
    }

    public function list()
    {
        list($files) = $this->getPages();
        $files = $this->table->findAll();

        return [
            'template' => 'pagelist.html.php',
            'title' => 'Page List',
            'variables' => [
                'files' => $files,
                'action' => '/pages/delete/',
                'perform' => 'delete',
                'exit' => BADMINTON
            ]
        ];
    }

    public function message($str = '', $i = 0)
    {
        $str = exclaim($str);
        if ($str) {
            return [
                'template' => 'accessdenied.html.php',
                'variables' => [
                    'str' => $str,
                    'accesslevel' => $this->getAccess($i),
                    'submitted' => false
                ]
            ];
        } else {
            retour();
        }
    }
}
