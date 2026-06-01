<?php

namespace NorthWolds\Entity;

class Entity
{
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


    protected function setCookie($data, $mandatory, bool $flag = false)
    {
        $setcookie = doSetCookie($flag);
        foreach ($mandatory as $prop) {
            $arg = $flag && !isset($data[$prop]) ? $data[$prop] : '';
            $setcookie($prop, $arg);
        }
    }

    public function find(...$args)
    {
     return $this->fetch(...$args);
    }
  
}
