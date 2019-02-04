<?php

namespace Kirby\ComposerInstaller;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

use Composer\Composer;
use Composer\Config;
use Composer\Downloader\DownloadManager;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Composer\Package\Link;
use Composer\Package\Package;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackage;
use Composer\Util\Filesystem;

class MockDownloadManager extends DownloadManager
{
    protected $io;
    protected $preferSource = false;
    protected $filesystem;

    public function __construct(IOInterface $io, $preferSource = false, Filesystem $filesystem = null)
    {
        // duplicated because the parent properties are declared private
        $this->io = $io;
        $this->preferSource = $preferSource;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    public function download(PackageInterface $package, $targetDir, $preferSource = null)
    {
        $targetDir = dirname(__DIR__) . '/tmp/' . $targetDir;

        // install a fake plugin directory
        $this->filesystem->ensureDirectoryExists($targetDir);
        touch($targetDir . '/index.php');

        // create a vendor dir if requested by the test
        if (!empty($package->getExtra()['with-vendor-dir'])) {
            $this->filesystem->ensureDirectoryExists($targetDir . '/vendor/test');
            touch($targetDir . '/vendor/test/test.txt');
            touch($targetDir . '/vendor-created.txt');
        }
    }
}

class PluginInstallerTest extends TestCase
{
    protected $testDir;
    protected $composer;
    protected $filesystem;
    protected $installer;

    public function setUp()
    {
        // change to the test dir
        $this->testDir = dirname(__DIR__) . '/tmp';
        if (!is_dir($this->testDir)) {
            mkdir($this->testDir);
        }
        chdir($this->testDir);

        // initialize new Composer and Installer instances
        $io = new NullIO();
        $config = new Config();
        $config->merge([
            'config' => [
                'vendor-dir' => $this->testDir . '/vendor'
            ]
        ]);
        $this->filesystem = new Filesystem();
        $this->composer = new Composer();
        $this->composer->setConfig($config);
        $this->composer->setDownloadManager(new MockDownloadManager($io, false, $this->filesystem));
        $this->installer = new PluginInstaller($io, $this->composer);
    }

    public function tearDown()
    {
        $this->filesystem->removeDirectory($this->testDir);
    }

    public function testSupports()
    {
        $this->assertTrue($this->installer->supports('kirby-plugin'));
        $this->assertFalse($this->installer->supports('kirby-cms'));
        $this->assertFalse($this->installer->supports('amazing-plugin'));
    }

    public function testGetInstallPathNoSupport()
    {
        $package = $this->pluginPackageFactory(false, false);
        $this->assertEquals($this->testDir . '/vendor/superwoman/superplugin', $this->installer->getInstallPath($package));
    }

    public function testGetInstallPathDefault()
    {
        $package = $this->pluginPackageFactory(true, false);
        $this->assertEquals('site/plugins/superplugin', $this->installer->getInstallPath($package));
    }

    public function testGetInstallPathNoVendor()
    {
        $package = new Package('superplugin', '1.0.0.0', '1.0.0');
        $package->setType('kirby-plugin');
        $package->setRequires([
            new Link('superwoman/superplugin', 'getkirby/composer-installer')
        ]);
        $this->assertEquals('site/plugins/superplugin', $this->installer->getInstallPath($package));
    }

    public function testGetInstallPathInstallerName()
    {
        $package = $this->pluginPackageFactory(true, false);
        $package->setExtra([
            'installer-name' => 'another-name'
        ]);
        $this->assertEquals('site/plugins/another-name', $this->installer->getInstallPath($package));
    }

    public function testGetInstallPathCustomPaths()
    {
        $rootPackage = new RootPackage('getkirby/amazing-site', '1.0.0.0', '1.0.0');
        $rootPackage->setExtra([
            'kirby-plugin-path' => 'data/plugins'
        ]);
        $this->composer->setPackage($rootPackage);

        $package = $this->pluginPackageFactory(true, false);
        $this->assertEquals('data/plugins/superplugin', $this->installer->getInstallPath($package));

        $package = $this->pluginPackageFactory(true, false);
        $package->setExtra([
            'installer-name' => 'another-name'
        ]);
        $this->assertEquals('data/plugins/another-name', $this->installer->getInstallPath($package));
    }

    public function testInstallCodeNoSupport()
    {
        $installCodeMethod = new ReflectionMethod($this->installer, 'installCode');
        $installCodeMethod->setAccessible(true);

        $rootPackage = new RootPackage('getkirby/amazing-site', '1.0.0.0', '1.0.0');
        $this->composer->setPackage($rootPackage);

        $package = $this->pluginPackageFactory(false, false);
        $this->assertEquals($this->testDir . '/vendor/superwoman/superplugin', $this->installer->getInstallPath($package));
        $installCodeMethod->invoke($this->installer, $package);
        $this->assertDirectoryNotExists($this->testDir . '/vendor/superwoman/superplugin');
    }

    public function testInstallCodeWithoutVendorDir()
    {
        $installCodeMethod = new ReflectionMethod($this->installer, 'installCode');
        $installCodeMethod->setAccessible(true);

        $rootPackage = new RootPackage('getkirby/amazing-site', '1.0.0.0', '1.0.0');
        $this->composer->setPackage($rootPackage);

        $package = $this->pluginPackageFactory(true, false);
        $this->assertEquals('site/plugins/superplugin', $this->installer->getInstallPath($package));
        $installCodeMethod->invoke($this->installer, $package);
        $this->assertFileExists($this->testDir . '/site/plugins/superplugin/index.php');
        $this->assertFileNotExists($this->testDir . '/site/plugins/superplugin/vendor-created.txt');
        $this->assertDirectoryNotExists($this->testDir . '/site/plugins/superplugin/vendor');
    }

    public function testInstallCodeVendorDir()
    {
        $installCodeMethod = new ReflectionMethod($this->installer, 'installCode');
        $installCodeMethod->setAccessible(true);

        $rootPackage = new RootPackage('getkirby/amazing-site', '1.0.0.0', '1.0.0');
        $this->composer->setPackage($rootPackage);

        $package = $this->pluginPackageFactory(true, true);
        $this->assertEquals('site/plugins/superplugin', $this->installer->getInstallPath($package));
        $installCodeMethod->invoke($this->installer, $package);
        $this->assertFileExists($this->testDir . '/site/plugins/superplugin/index.php');
        $this->assertFileExists($this->testDir . '/site/plugins/superplugin/vendor-created.txt');
        $this->assertDirectoryNotExists($this->testDir . '/site/plugins/superplugin/vendor');
    }

    /**
     * Creates a dummy plugin package
     *
     * @param  bool    $supported Whether the plugin package has required the installer
     * @param  bool    $vendorDir Whether the plugin has a vendor directory committed
     * @return Package
     */
    protected function pluginPackageFactory(bool $supported, bool $vendorDir): Package
    {
        $package = new Package('superwoman/superplugin', '1.0.0.0', '1.0.0');
        $package->setType('kirby-plugin');

        if ($supported === true) {
            $package->setRequires([
                new Link('superwoman/superplugin', 'getkirby/composer-installer')
            ]);
        }

        if ($vendorDir === true) {
            $package->setExtra([
                'with-vendor-dir' => true
            ]);
        }

        return $package;
    }
}
