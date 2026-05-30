<?php
/*note Browsers or Managers cannot edit their own details but the details may be accessed by an Admin role
so we extend ClientAdmin in order to validate role or domain changes*/

namespace NorthWolds\Entity;

class Browser extends User
{
    public function __construct(...$args)
    {
        parent::__construct(...$args);
    }
}
