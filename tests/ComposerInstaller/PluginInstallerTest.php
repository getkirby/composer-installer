<?php

namespace Kirby\ComposerInstaller;

use PHPUnit\Framework\TestCase;

use Composer\Composer;
use Composer\Config;
use Composer\IO\NullIO;
use Composer\Package\Link;
use Composer\Package\Package;
use Composer\Package\RootPackage;
use Composer\Repository\InstalledArrayRepository;
use Composer\Util\Filesystem;

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

    public function testInstallNoSupport()
    {
        $rootPackage = new RootPackage('getkirby/amazing-site', '1.0.0.0', '1.0.0');
        $this->composer->setPackage($rootPackage);

        $package = $this->pluginPackageFactory(false, true);
        $this->assertEquals($this->testDir . '/vendor/superwoman/superplugin', $this->installer->getInstallPath($package));
        $this->installer->install(new InstalledArrayRepository(), $package);
        $this->assertFileExists($this->testDir . '/vendor/superwoman/superplugin/index.php');
        $this->assertFileExists($this->testDir . '/vendor/superwoman/superplugin/vendor-created.txt');
        $this->assertDirectoryExists($this->testDir . '/vendor/superwoman/superplugin/vendor');
    }

    public function testInstallWithoutVendorDir()
    {
        $rootPackage = new RootPackage('getkirby/amazing-site', '1.0.0.0', '1.0.0');
        $this->composer->setPackage($rootPackage);

        $package = $this->pluginPackageFactory(true, false);
        $this->assertEquals('site/plugins/superplugin', $this->installer->getInstallPath($package));
        $this->installer->install(new InstalledArrayRepository(), $package);
        $this->assertFileExists($this->testDir . '/site/plugins/superplugin/index.php');
        $this->assertFileNotExists($this->testDir . '/site/plugins/superplugin/vendor-created.txt');
        $this->assertDirectoryNotExists($this->testDir . '/site/plugins/superplugin/vendor');
    }

    public function testInstallVendorDir()
    {
        $rootPackage = new RootPackage('getkirby/amazing-site', '1.0.0.0', '1.0.0');
        $this->composer->setPackage($rootPackage);

        $package = $this->pluginPackageFactory(true, true);
        $this->assertEquals('site/plugins/superplugin', $this->installer->getInstallPath($package));
        $this->installer->install(new InstalledArrayRepository(), $package);
        $this->assertFileExists($this->testDir . '/site/plugins/superplugin/index.php');
        $this->assertFileExists($this->testDir . '/site/plugins/superplugin/vendor-created.txt');
        $this->assertDirectoryNotExists($this->testDir . '/site/plugins/superplugin/vendor');
    }

    public function testUpdateNoSupport()
    {
        $repo = new InstalledArrayRepository();

        $rootPackage = new RootPackage('getkirby/amazing-site', '1.0.0.0', '1.0.0');
        $this->composer->setPackage($rootPackage);

        $initial = $this->pluginPackageFactory(false, true);
        $this->assertEquals($this->testDir . '/vendor/superwoman/superplugin', $this->installer->getInstallPath($initial));
        $this->installer->install($repo, $initial);
        $repo->addPackage($initial);
        $this->assertFileExists($this->testDir . '/vendor/superwoman/superplugin/index.php');
        $this->assertFileExists($this->testDir . '/vendor/superwoman/superplugin/vendor-created.txt');
        $this->assertDirectoryExists($this->testDir . '/vendor/superwoman/superplugin/vendor');

        unlink($this->testDir . '/vendor/superwoman/superplugin/index.php');
        $this->assertFileNotExists($this->testDir . '/vendor/superwoman/superplugin/index.php');

        $target = $this->pluginPackageFactory(false, true);
        $this->assertEquals($this->testDir . '/vendor/superwoman/superplugin', $this->installer->getInstallPath($target));
        $this->installer->update($repo, $initial, $target);
        $this->assertFileExists($this->testDir . '/vendor/superwoman/superplugin/index.php');
        $this->assertFileExists($this->testDir . '/vendor/superwoman/superplugin/vendor-created.txt');
        $this->assertDirectoryExists($this->testDir . '/vendor/superwoman/superplugin/vendor');
    }

    public function testUpdateWithoutVendorDir()
    {
        $repo = new InstalledArrayRepository();

        $rootPackage = new RootPackage('getkirby/amazing-site', '1.0.0.0', '1.0.0');
        $this->composer->setPackage($rootPackage);

        $initial = $this->pluginPackageFactory(true, false);
        $this->assertEquals('site/plugins/superplugin', $this->installer->getInstallPath($initial));
        $this->installer->install($repo, $initial);
        $repo->addPackage($initial);
        $this->assertFileExists($this->testDir . '/site/plugins/superplugin/index.php');
        $this->assertFileNotExists($this->testDir . '/site/plugins/superplugin/vendor-created.txt');
        $this->assertDirectoryNotExists($this->testDir . '/site/plugins/superplugin/vendor');

        unlink($this->testDir . '/site/plugins/superplugin/index.php');
        $this->assertFileNotExists($this->testDir . '/site/plugins/superplugin/index.php');

        $target = $this->pluginPackageFactory(true, false);
        $this->assertEquals('site/plugins/superplugin', $this->installer->getInstallPath($target));
        $this->installer->update($repo, $initial, $target);
        $this->assertFileExists($this->testDir . '/site/plugins/superplugin/index.php');
        $this->assertFileNotExists($this->testDir . '/site/plugins/superplugin/vendor-created.txt');
        $this->assertDirectoryNotExists($this->testDir . '/site/plugins/superplugin/vendor');
    }

    public function testUpdateVendorDir()
    {
        $repo = new InstalledArrayRepository();

        $rootPackage = new RootPackage('getkirby/amazing-site', '1.0.0.0', '1.0.0');
        $this->composer->setPackage($rootPackage);

        $initial = $this->pluginPackageFactory(true, true);
        $this->assertEquals('site/plugins/superplugin', $this->installer->getInstallPath($initial));
        $this->installer->install($repo, $initial);
        $repo->addPackage($initial);
        $this->assertFileExists($this->testDir . '/site/plugins/superplugin/index.php');
        $this->assertFileExists($this->testDir . '/site/plugins/superplugin/vendor-created.txt');
        $this->assertDirectoryNotExists($this->testDir . '/site/plugins/superplugin/vendor');

        unlink($this->testDir . '/site/plugins/superplugin/index.php');
        $this->assertFileNotExists($this->testDir . '/site/plugins/superplugin/index.php');

        $target = $this->pluginPackageFactory(true, true);
        $this->assertEquals('site/plugins/superplugin', $this->installer->getInstallPath($target));
        $this->installer->update($repo, $initial, $target);
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
        $package->setInstallationSource('dist');
        $package->setDistType('mock');

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
