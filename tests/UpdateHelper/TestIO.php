<?php

namespace UpdateHelper\Tests;

use Composer\IO\NullIO;

class TestIO extends NullIO
{
    protected $lastError = null;

    protected $lastOutput = null;

    protected $interactive = false;

    public function reset()
    {
        $this->lastOutput = null;
        $this->lastError = null;
    }

    public function getLastOutput()
    {
        return $this->lastOutput;
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function setInteractive($interactive)
    {
        $this->interactive = $interactive;
    }

    public function isInteractive()
    {
        return $this->interactive;
    }

    public function write($messages, $newline = true, $verbosity = self::NORMAL)
    {
        $this->lastOutput = $messages;
    }

    public function writeError($messages, $newline = true, $verbosity = self::NORMAL)
    {
        $this->lastError = $messages;
    }
}
