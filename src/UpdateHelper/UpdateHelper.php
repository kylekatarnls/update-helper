<?php

namespace UpdateHelper;

use Composer\Composer;
use Composer\EventDispatcher\Event;
use Composer\Installer\PackageEvent;
use Composer\Json\JsonFile;
use Composer\Script\Event as ScriptEvent;

class UpdateHelper
{
    protected static function appendConfig(&$classes, $directory, $key = null)
    {
        $file = $directory.DIRECTORY_SEPARATOR.'composer.json';
        $json = new JsonFile($file);
        $key = $key ? $key : 'update-helper';

        try {
            $dependencyConfig = $json->read();
        } catch (\RuntimeException $e) {
            $dependencyConfig = null;
        }

        if (is_array($dependencyConfig) && isset($dependencyConfig['extra'], $dependencyConfig['extra'][$key])) {
            $classes[$file] = $dependencyConfig['extra'][$key];
        }
    }

    protected static function getUpdateHelperConfig(Composer $composer, $key = null)
    {
        $vendorDir = $composer->getConfig()->get('vendor-dir');

        $npm = array();

        foreach (scandir($vendorDir) as $namespace) {
            if ($namespace === '.' || $namespace === '..' || !is_dir($directory = $vendorDir.DIRECTORY_SEPARATOR.$namespace)) {
                continue;
            }

            foreach (scandir($directory) as $dependency) {
                if ($dependency === '.' || $dependency === '..' || !is_dir($subDirectory = $directory.DIRECTORY_SEPARATOR.$dependency)) {
                    continue;
                }

                static::appendConfig($npm, $subDirectory, $key);
            }
        }

        static::appendConfig($npm, dirname($vendorDir), $key);

        return $npm;
    }

    public static function check(Event $event)
    {
        if ($event instanceof ScriptEvent || $event instanceof PackageEvent) {
            $io = $event->getIO();
            $composer = $event->getComposer();
            $autoload = __DIR__.'/../../../../autoload.php';

            if (file_exists($autoload)) {
                include_once $autoload;
            }

            $classes = static::getUpdateHelperConfig($composer);

            foreach ($classes as $file => $class) {
                $error = null;

                if (is_string($class) && class_exists($class)) {
                    try {
                        $helper = new $class();
                    } catch (\Exception $e) {
                        $error = $e->getMessage()."\nFile: ".$e->getFile()."\nLine:".$e->getLine()."\n\n".$e->getTraceAsString();
                    } catch (\Throwable $e) {
                        $error = $e->getMessage()."\nFile: ".$e->getFile()."\nLine:".$e->getLine()."\n\n".$e->getTraceAsString();
                    }

                    if (!$error && $helper instanceof UpdateHelperInterface) {
                        $helper->check($event, $io, $composer);

                        continue;
                    }
                }

                if (!$error) {
                    $error = json_encode($class).' is not an instance of UpdateHelperInterface.';
                }

                $io->writeError('UpdateHelper error in '.$file.":\n".$error);
            }
        }
    }
}
