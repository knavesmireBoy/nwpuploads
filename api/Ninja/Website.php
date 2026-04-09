<?php

namespace Ninja;

interface Website
{
    public function getDefaultRoute(): string;
    public function getController(string $controllerName, array $args, array $optional_args): ?object;
    public function checkLogin(string $uri): array;
    public function getLayoutVariables($key): array;
    public function getScripts(): array;
    public function getControllerArgs($key):array;
    public function setNavBar():array;
    public function create($name): void;
    public function setHome($name): string;
}
