<?php

namespace UpdateHelper\Tests;

use Composer\Composer;
use Composer\Config;
use Composer\Config\JsonConfigSource;
use Composer\DependencyResolver\DefaultPolicy;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Request;
use Composer\EventDispatcher\Event;
use Composer\Installer\PackageEvent;
use Composer\Json\JsonFile;
use Composer\Package\Package;
use Composer\Repository\CompositeRepository;
use Composer\Repository\RepositorySet;
use Composer\Script\Event as ScriptEvent;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use UpdateHelper\UpdateHelper;

class UpdateHelperTest extends TestCase
{
    /**
     * @var string
     */
    protected $currentDirectory;

    /**
     * @var Event
     */
    protected $event;

    /**
     * @var TestIO
     */
    protected $io;

    /**
     * @var UpdateHelper
     */
    protected $helper;

    protected function setUp()
    {
        $this->currentDirectory = getcwd();
        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();
        chdir($this->currentDirectory);
    }

    /**
     * @return UpdateHelper
     */
    protected function buildHelper()
    {
        $this->event = new Event('fake');
        $this->io = new TestIO();
        $this->helper = new UpdateHelper($this->event, $this->io);

        return $this->helper;
    }

    protected function getPackageEvent($composer, $io)
    {
        if (intval(Composer::getVersion()) >= 2) {
            return new PackageEvent(
                'update',
                $composer,
                $io,
                false,
                new CompositeRepository(array()),
                array(),
                new InstallOperation(new Package('foo/bar', '1.0.0', '1.0.0'))
            );
        }

        return new PackageEvent(
            'update',
            $composer,
            $io,
            false,
            new DefaultPolicy(true, false),
            new RepositorySet(),
            new CompositeRepository(array()),
            new Request(),
            array(),
            new InstallOperation(new Package('foo/bar', '1.0.0', '1.0.0'))
        );
    }

    /**
     * @return UpdateHelper
     */
    protected function buildRichHelper()
    {
        chdir(__DIR__.'/mixtures/app4');
        $composer = new Composer();
        $config = new Config();
        $config->setConfigSource(new JsonConfigSource(new JsonFile('composer.json')));
        $composer->setConfig($config);
        $io = new TestIO();
        $event = $this->getPackageEvent($composer, $io);

        return new UpdateHelper($event, $io);
    }

    /**
     * @throws \ReflectionException
     */
    public function testAppendConfig()
    {
        $classes = array();
        $class = new ReflectionClass('UpdateHelper\\UpdateHelper');
        /** @var ReflectionMethod $method */
        $method = $class->getMethod('appendConfig');
        $method->setAccessible(true);
        $method->invokeArgs(null, array(&$classes, __DIR__.'/mixtures/app1'));

        self::assertCount(1, $classes);

        $key = key($classes);

        self::assertStringEndsWith('app1'.DIRECTORY_SEPARATOR.'composer.json', $key);

        self::assertSame(array('Foo\\Bar'), $classes[$key]);

        $method->invokeArgs(null, array(&$classes, __DIR__.'/mixtures/app1', 'other'));

        self::assertSame(array('A\\B', 'C\\D'), $classes[$key]);

        $method->invokeArgs(null, array(&$classes, __DIR__.'/mixtures/app2'));
        $method->invokeArgs(null, array(&$classes, __DIR__.'/mixtures/app3'));

        self::assertCount(1, $classes);
        self::assertSame(array('A\\B', 'C\\D'), $classes[$key]);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetUpdateHelperConfig()
    {
        $composer = new Composer();
        $config = new Config();
        $config->merge(array(
            'config' => array(
                'vendor-dir' => __DIR__.'/mixtures/app1/vendor',
            ),
        ));
        $composer->setConfig($config);

        $class = new ReflectionClass('UpdateHelper\\UpdateHelper');
        /** @var ReflectionMethod $method */
        $method = $class->getMethod('getUpdateHelperConfig');
        $method->setAccessible(true);

        $npm = $method->invoke(null, $composer);

        self::assertCount(2, $npm);

        $keys = array_keys($npm);

        self::assertStringEndsWith('/app1/vendor/my-namespace/my-package/composer.json', str_replace('\\', '/', $keys[0]));
        self::assertStringEndsWith('/app1/composer.json', str_replace('\\', '/', $keys[1]));

        self::assertSame(array('MyNamespace\\MyPackage'), $npm[$keys[0]]);
        self::assertSame(array('Foo\\Bar'), $npm[$keys[1]]);
    }

    public function testCheck()
    {
        $composer = new Composer();
        $config = new Config();
        $config->merge(array(
            'config' => array(
                'vendor-dir' => __DIR__.'/mixtures/app1/vendor',
            ),
        ));
        $composer->setConfig($config);
        $this->buildHelper();
        $io = new TestIO();
        $event = new ScriptEvent(
            'update',
            $composer,
            $io
        );

        UpdateHelper::check($event);

        self::assertNull($io->getLastOutput());
        self::assertRegExp('/^UpdateHelper error in [\\s\\S]*Foo\\\\+Bar[\\s\\S]* is not an instance of UpdateHelperInterface.$/', $io->getLastError());
    }

    public function testConstruct()
    {
        self::assertInstanceOf('UpdateHelper\\UpdateHelper', $this->buildHelper());
        self::assertInstanceOf('UpdateHelper\\UpdateHelper', $this->buildRichHelper());
    }

    public function testGetFile()
    {
        self::assertNull($this->buildHelper()->getFile());
        self::assertSame(array(
            'config'      => array(
                'vendor-dir' => 'foo',
            ),
            'require'     => array(
                'a/b' => '^1.0',
            ),
            'require-dev' => array(
                'c/d' => '^2.0',
            ),
            'extra'       => array(
                'update-helper' => array(
                    'Foo\\Biz',
                ),
            ),
        ), $this->buildRichHelper()->getFile()->read());
    }

    public function testGetComposerFilePath()
    {
        self::assertNull($this->buildHelper()->getComposerFilePath());
        self::assertSame('./composer.json', $this->buildRichHelper()->getComposerFilePath());
    }

    public function testGetComposer()
    {
        self::assertNull($this->buildHelper()->getComposer());
        self::assertInstanceOf('Composer\\Composer', $this->buildRichHelper()->getComposer());
    }

    public function testGetEvent()
    {
        self::assertInstanceOf('Composer\\EventDispatcher\\Event', $this->buildHelper()->getEvent());
        self::assertInstanceOf('Composer\\EventDispatcher\\Event', $this->buildRichHelper()->getEvent());
    }

    public function testGetIo()
    {
        self::assertInstanceOf('Composer\\IO\\IOInterface', $this->buildHelper()->getIo());
        self::assertInstanceOf('Composer\\IO\\IOInterface', $this->buildRichHelper()->getIo());
    }

    public function testGetDependencies()
    {
        self::assertSame(array(), $this->buildHelper()->getDependencies());
        self::assertSame(array(
            'config'      => array(
                'vendor-dir' => 'foo',
            ),
            'require'     => array(
                'a/b' => '^1.0',
            ),
            'require-dev' => array(
                'c/d' => '^2.0',
            ),
            'extra'       => array(
                'update-helper' => array(
                    'Foo\\Biz',
                ),
            ),
        ), $this->buildRichHelper()->getDependencies());
    }

    public function testGetDevDependencies()
    {
        self::assertSame(array(), $this->buildHelper()->getDevDependencies());
        self::assertSame(array(
            'c/d' => '^2.0',
        ), $this->buildRichHelper()->getDevDependencies());
    }

    public function testGetProdDependencies()
    {
        self::assertSame(array(), $this->buildHelper()->getProdDependencies());
        self::assertSame(array(
            'a/b' => '^1.0',
        ), $this->buildRichHelper()->getProdDependencies());
    }

    public function testGetFlattenDependencies()
    {
        self::assertSame(array(), $this->buildHelper()->getFlattenDependencies());
        self::assertSame(array(
            'c/d' => '^2.0',
            'a/b' => '^1.0',
        ), $this->buildRichHelper()->getFlattenDependencies());
    }

    public function testHasAsDevDependency()
    {
        $helper = $this->buildHelper();
        $rightHelper = $this->buildRichHelper();
        self::assertFalse($helper->hasAsDevDependency('c/d'));
        self::assertFalse($helper->hasAsDevDependency('a/b'));
        self::assertTrue($rightHelper->hasAsDevDependency('c/d'));
        self::assertFalse($rightHelper->hasAsDevDependency('a/b'));
    }

    public function testHasAsProdDependency()
    {
        $helper = $this->buildHelper();
        $rightHelper = $this->buildRichHelper();
        self::assertFalse($helper->hasAsProdDependency('c/d'));
        self::assertFalse($helper->hasAsProdDependency('a/b'));
        self::assertFalse($rightHelper->hasAsProdDependency('c/d'));
        self::assertTrue($rightHelper->hasAsProdDependency('a/b'));
    }

    public function testHasAsDependency()
    {
        $helper = $this->buildHelper();
        $rightHelper = $this->buildRichHelper();
        self::assertFalse($helper->hasAsDependency('c/d'));
        self::assertFalse($helper->hasAsDependency('a/b'));
        self::assertTrue($rightHelper->hasAsDependency('c/d'));
        self::assertTrue($rightHelper->hasAsDependency('a/b'));
    }

    public function testIsDependencyAtLeast()
    {
        $helper = $this->buildHelper();
        $rightHelper = $this->buildRichHelper();
        self::assertFalse($helper->isDependencyAtLeast('c/d', '1.5'));
        self::assertFalse($helper->isDependencyAtLeast('a/b', '1.5'));
        self::assertFalse($rightHelper->isDependencyAtLeast('c/d', '1.5'));
        self::assertTrue($rightHelper->isDependencyAtLeast('a/b', '1.5'));
        self::assertFalse($helper->isDependencyAtLeast('c/d', '2.3'));
        self::assertFalse($helper->isDependencyAtLeast('a/b', '2.3'));
        self::assertTrue($rightHelper->isDependencyAtLeast('c/d', '2.3'));
        self::assertFalse($rightHelper->isDependencyAtLeast('a/b', '2.3'));
    }

    public function testIsDependencyLesserThan()
    {
        $helper = $this->buildHelper();
        $rightHelper = $this->buildRichHelper();
        self::assertTrue($helper->isDependencyLesserThan('c/d', '1.5'));
        self::assertTrue($helper->isDependencyLesserThan('a/b', '1.5'));
        self::assertTrue($rightHelper->isDependencyLesserThan('c/d', '1.5'));
        self::assertFalse($rightHelper->isDependencyLesserThan('a/b', '1.5'));
        self::assertTrue($helper->isDependencyLesserThan('c/d', '2.3'));
        self::assertTrue($helper->isDependencyLesserThan('a/b', '2.3'));
        self::assertFalse($rightHelper->isDependencyLesserThan('c/d', '2.3'));
        self::assertTrue($rightHelper->isDependencyLesserThan('a/b', '2.3'));
    }

    /**
     * @throws \Exception
     */
    public function testSetDependencyVersion()
    {
        $helper = $this->buildRichHelper();
        self::assertFalse($helper->isDependencyAtLeast('c/d', '3.3.79'));
        $helper->setDependencyVersion('c/d', '~3.3.2');
        self::assertTrue($helper->isDependencyAtLeast('c/d', '3.3.79'));
        $helper->setDependencyVersion('c/d', '^2.0');
    }

    /**
     * @throws \Exception
     */
    public function testSetDependencyVersions()
    {
        $helper = $this->buildRichHelper();
        self::assertFalse($helper->isDependencyAtLeast('c/d', '3.3.79'));
        $helper->setDependencyVersions(array('c/d' => '~3.3.2'));
        self::assertTrue($helper->isDependencyAtLeast('c/d', '3.3.79'));
        $helper->setDependencyVersions(array('c/d' => '^2.0'));
    }

    /**
     * @throws \Exception
     */
    public function testUpdate()
    {
        self::markTestIncomplete('Test for -> update() to be implemented');
    }

    public function testWrite()
    {
        $helper = $this->buildHelper();
        $helper->write('Hello');
        self::assertSame('Hello', $this->io->getLastOutput());
    }

    public function testIsInteractive()
    {
        $helper = $this->buildHelper();
        $this->io->setInteractive(false);
        self::assertFalse($helper->isInteractive());
        $this->io->setInteractive(true);
        self::assertTrue($helper->isInteractive());
    }
}
