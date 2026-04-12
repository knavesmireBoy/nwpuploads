<?php

namespace Ninja;
var_dump(file_exists(__DIR__ . '../config.php'), file_exists('../config.php'));
//include_once __DIR__ . '../config.php';
//include_once FUNCTIONS;

class Authentication
{
    private $users;
    private $usernameColumn;
    private $passwordColumn;

    private function find($k, $v)
    {
        $user = $this->users->find($k, $v);
        if (!empty($user)) {
            return $user;
        } else {
            return null;
        }
    }

    public function __construct(DatabaseTable $users, string $usr, string $pwd)
    {
        //startSession();
        $this->users = $users;
        $this->usernameColumn = $usr;
        $this->passwordColumn = $pwd;
    }

    public function login1(string $username, string $password): bool
    {
        $user = $this->find($this->usernameColumn, $username);
        if ($user) {
            $user = $user[0];
            if (!empty($user) && password_verify($password, $user->{$this->passwordColumn})) {
                session_regenerate_id();
                $_SESSION['username'] = $username;
                $_SESSION['password'] = $user->{$this->passwordColumn};
                return true;
            }
        }
        return false;
    }


    public function login(string $username, string $password): bool
    {
        $user = $this->find($this->usernameColumn, $username);
        if ($user) {
            $user = $user[0];
            if (!empty($user) && ($password == $user->{$this->passwordColumn})) {
                session_regenerate_id();
                $_SESSION['username'] = $username;
                $_SESSION['password'] = $user->{$this->passwordColumn};
                return true;
            }
        }
        return false;
    }


    public function isLoggedIn(): ?object
    {
        if (empty($_SESSION['username'])) {
            return null;
        }
        $user = $this->find($this->usernameColumn, $_SESSION['username']);
        $user = $user[0] ?? null;
        if ($user && $user->{$this->passwordColumn} === $_SESSION['password']) {
            return $user;
        }
        return null;
    }
    public function logout()
    {
        unset($_SESSION['username']);
        unset($_SESSION['password']);
        unset($_SESSION['filestore']);
        session_regenerate_id();
    }
}

//https://itnext.io/how-to-implement-password-recovery-securely-in-php-db2275ab3560
//$token = bin2hex(random_bytes(16));