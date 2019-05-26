# update-helper

Allow you to display update/upgrade instructions to your library users.

## Usage

```json
...
"require": {
    "kylekatarnls/update-helper": "^1"
},
"require-dev": {
    "composer/composer": "^1.2",
},
"extra": {
    "update-helper": "MyNamesapace\\MyUpdateHelper"
},
"scripts": {
    "post-autoload-dump": [
        "UpdateHelper\\UpdateHelper::check"
    ]
},
...
```


```php
namespace MyNamesapace;

use UpdateHelper\UpdateHelper;
use UpdateHelper\UpdateHelperInterface;

class MyUpdateHelper implements UpdateHelperInterface
{
    public function check(UpdateHelper $helper)
    {
        $helper->write("You're using an obsolete version of my-super-package, consider upgrading to version 2 or greater.");

        if ($helper->hasAsDependency('laravel/framework') && $helper->isDependencyLesserThan('laravel/framework', '5.0.0')) {
            $helper->write("You're using a very old version or Laravel we don't support, please consider upgrading at least to 5.0.0.");
        }

        if ($helper->isInteractive()) {
            if ($helper->getIo()->askConfirmation('Do you want us to upgrade it for you?')) {
                $helper->setDependencyVersions(array(
                    'my-vendor/my-super-package' => '^2.0.0',
                    'laravel/framework' => '^5.0.0', // Skip it if not installed
                ))->update();
            }
        }
    }
}
```
