<?php

namespace NorthWolds\Controllers;

class UsersRepository implements \SplSubject
{
    private \SplObjectStorage $observers;

    private array $users = [];
    private static $instance;
    private function __construct()
    {
        $this->observers = new \SplObjectStorage;
    }
    private function __clone() {}
    public function createUser(User $user): void
    {
        $this->users[] = $user;
        $this->notify();
    }
    public function attach(\SplObserver $observer): void
    {
        $this->observers->attach($observer);
    }
    public function detach(\SplObserver $observer): void
    {
        $this->observers->detach($observer);
    }
    public function notify(): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
    }
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }
        return self::$instance;
    }
}
