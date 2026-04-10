<?php

namespace Ninja;

class EntryPoint
{
    private $website;
    private $posts;
    private $scripts;

    function loadTemplate($templateFileName, $variables)
    {
        extract($variables);
        ob_start();
        include  TEMPLATE . $templateFileName;
        return ob_get_clean();
    }

    private function checkUri($uri)
    {
        if ($uri !== strtolower($uri)) {
            http_response_code(301);
            reLocate(REG);
        }
    }
    //posts = ['edit', 'delete'] each member would have the word Submit appended to distinguish get and post requests
    public function __construct(Website $website, array $posts)
    {
        $this->website = $website;
        $this->posts = $posts;
    }

    private function reroute($name, $action)
    {
        return empty($action) ? $name : $action;
    }

    public function run($uri, $method, $defaultKlas = '', $home = 'home')
    {
        try {
            $this->checkUri($uri);
            if ($uri == '') {
                $this->website->setHome($home);
                $uri = $this->website->getDefaultRoute();
            }
           
            $output = '';
            $route = explode('/', $uri);
            $name = array_shift($route);
            $action = array_shift($route);
            var_dump($name, $action);
            $controller = new \stdClass();
            $args = $this->website->getControllerArgs($name, $controller);


            if ($method === 'POST' && in_array($action, $this->posts)) {
                $action .= 'Submit';
            }
           // $action = $this->reroute($name, $action);
            //$public_page = $name === $action;
            //$action = $public_page ? 'display' : $action;
            $user = $this->website->checkLogin($name . '/' . $action); //: array
         
            $userid = $user[0]->id ?? 0;
            $userpermissions = $user[1] ?? 0;
            $controller = $this->website->getController($name, $args, [$userid, $userpermissions]);
            
            if (is_callable([$controller, $action])) {
                //$this->website->create($name);
                $page = $controller->$action(...$route);
                //one could type for example editsubmit/1 in browser address bar
                if ($page && is_array($page)) {
                    $vars = array_merge($this->website->getLayoutVariables('login'), $page['variables'] ?? []);
                    $output = $this->loadTemplate($page['template'], $vars);
                } else {
                    retour();
                }
            } else {
                http_response_code(404);
                $layoutVariables['title'] = 'lost';
                $layoutVariables['klas'] = "notfound";
                $layoutVariables['scripts'] = [];
                $output = '<p>Sorry, the page you are looking for could not be found.</p>';
            }
        } catch (\PDOException $e) {
            $output = 'Database error: ' . $e->getMessage() . ' in ' .
                $e->getFile() . ':' . $e->getLine();
        }

        if (!isset($layoutVariables)) { //'not found'
            $layoutVariables = $this->website->getLayoutVariables($name . '/' . $action);

            /*
            $layoutVariables['klas'] = $layoutVariables['klas'] ?? $defaultKlas;
            $layoutVariables['scripts'] = $this->website->getScripts($name . '/' . $action);
            $layoutVariables['user'] = !empty($user) ? $user[0]->name : null;
            $layoutVariables['pageid'] = strtolower($layoutVariables['title']);
            $layoutVariables['nav'] =  $_SESSION['nav'] ?? [];
            */

            if (is_callable([$controller, 'setNavBar']) && $public_page) {
                unset($_SESSION['pending']);
                list($ids, $titles) = $this->website->setNavBar();
                $ids = array_map('strtolower', $ids);
                $i = array_search($name, $ids) ?? 0;
                $controller->setNavBar(); //determine live articles are present on page
                $layoutVariables['nav'] = $_SESSION['nav'];
                $layoutVariables['title'] = $titles[$i] ?? '';
                $layoutVariables['pageid'] = $ids[$i] ?? 0;
            }

            if (is_callable([$controller, 'getProp'])) {
                $pp = $controller->getProp('name', $name);
                $layoutVariables['description'] = $pp->description ?? '';
                $layoutVariables['content'] = $pp->content ?? '';
            }
        }
        if (isset($_SESSION['nav'])) {
            $layoutVariables['nav'] =  $_SESSION['nav'];
        } else {
            //fallback if ADMIN is accessed directly instead of starting from a public page
            list($pagenames) = $this->website->setNavBar();
            $navlist = [];
            foreach ($pagenames as $k => $v) {
                $k = strtolower("/$v/");
                $navlist[$k] = $v;
            }
            $layoutVariables['nav'] = $navlist;
        }
        $layoutVariables['output'] = $output;

        $routes = array_keys($layoutVariables['nav']);

        if ($name === 'home' && !in_array('/home/', $routes)) {
            reLocate(current($routes));
        }
        return $layoutVariables;
    }
}
