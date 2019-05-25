<?php

namespace UpdateHelper;

use Composer\Composer;
use Composer\EventDispatcher\Event;
use Composer\IO\IOInterface;

interface UpdateHelperInterface
{
    public function check(Event $event, IOInterface $io, Composer $composer, UpdateHelper $helper);
}
