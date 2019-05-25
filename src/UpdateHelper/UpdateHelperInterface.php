<?php

namespace UpdateHelper;

use Composer\Composer;
use Composer\EventDispatcher\Event;
use Composer\IO\IOInterface;

interface UpdateHelperInterface
{
    public function check(Event $event = null, IOInterface $io = null, Composer $composer = null, UpdateHelper $helper = null);
}
