# update-helper

Allow you to display update/upgrade instructions to your library users.

## Usage

```json
...
"require": {
    "kylekatarnls/update-helper": "^1"
},
"extra": {
    "update-helper": "MyNamesapace\\MyUpdateHelper"
},
"scripts": {
    "post-install-cmd": [
        "UpdateHelper\\UpdateHelper::check"
    ],
    "post-update-cmd": [
        "UpdateHelper\\UpdateHelper::check"
    ]
},
...
```


```php
namespace MyNamesapace;

use Composer\Composer;
use Composer\EventDispatcher\Event;
use Composer\IO\IOInterface;
use UpdateHelper\UpdateHelperInterface;

class MyUpdateHelper implements UpdateHelperInterface
{
    /**
     * @param \Composer\Installer\PackageEvent|Composer\Script\Event $event
     * @param \Composer\IO\IOInterface                               $io
     * @param \Composer\Composer                                     $composer
     */
    public function check(Event $event, IOInterface $io, Composer $composer)
    {
        $io->write("You're using an obsolete version of my-super-package, consider upgrading to version 2 or greater.");
    }
}
```
