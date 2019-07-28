<?php

namespace Foo;

use UpdateHelper\UpdateHelper;
use UpdateHelper\UpdateHelperInterface;

class Biz implements UpdateHelperInterface
{
    public function check(UpdateHelper $helper)
    {
        $helper->write('Hello');
    }
}
