<?php

namespace Kirby\ComposerInstaller;

use Composer\Package\Link;
use Composer\Package\Package;
use Composer\Repository\InstalledArrayRepository;

class PluginInstallerTest extends InstallerTestCase
{
    const SUPPORTED  = 1;
    const VENDOR_DIR = 2;

    public function setUp()
    {
        parent::setUp();

        $this->installer = new PluginInstaller($this->io, $this->composer);
    }

    public function testSupports()
    {
        $this->assertTrue($this->installer->supports('kirby-plugin'));
        $this->assertFalse($this->installer->supports('kirby-cms'));
        $this->assertFalse($this->installer->supports('amazing-plugin'));
    }

    public function testGetInstallPathNoSupport()
    {
        $package = $this->pluginPackageFactory();
        $this->assertEquals($this->testDir . '/vendor/superwoman/superplugin', $this->installer->getInstallPath($package));
    }

    public function testGetInstallPathDefault()
    {
        $package = $this->pluginPackageFactory(self::SUPPORTED);
        $this->assertEquals('site/plugins/superplugin', $this->installer->getInstallPath($package));
    }

    public function testGetInstallPathNoVendor()
    {
        $package = $this->pluginPackageFactory(self::SUPPORTED, 'superplugin');
        $this->assertEquals('superplugin', $package->getPrettyName());
        $this->assertEquals('site/plugins/superplugin', $this->installer->getInstallPath($package));
    }

    public function testGetInstallPathInstallerName()
    {
        $package = $this->pluginPackageFactory(self::SUPPORTED);
        $package->setExtra([
            'installer-name' => 'another-name'
        ]);
        $this->assertEquals('site/plugins/another-name', $this->installer->getInstallPath($package));
    }

    public function testGetInstallPathCustomPaths()
    {
        $this->initRootPackage()->setExtra([
            'kirby-plugin-path' => 'data/plugins'
        ]);

        $package = $this->pluginPackageFactory(self::SUPPORTED);
        $this->assertEquals('data/plugins/superplugin', $this->installer->getInstallPath($package));

        $package = $this->pluginPackageFactory(self::SUPPORTED);
        $package->setExtra([
            'installer-name' => 'another-name'
        ]);
        $this->assertEquals('data/plugins/another-name', $this->installer->getInstallPath($package));
    }

    public function testInstallNoSupport()
    {
        $package = $this->pluginPackageFactory(self::VENDOR_DIR);
        $this->assertEquals($this->testDir . '/vendor/superwoman/superplugin', $this->installer->getInstallPath($package));
        $this->installer->install(new InstalledArrayRepository(), $package);
        $this->assertFileExists($this->testDir . '/vendor/superwoman/superplugin/index.php');
        $this->assertFileExists($this->testDir . '/vendor/superwoman/superplugin/vendor-created.txt');
        $this->assertDirectoryExists($this->testDir . '/vendor/superwoman/superplugin/vendor');
    }

    public function testInstallWithoutVendorDir()
    {
        $package = $this->pluginPackageFactory(self::SUPPORTED);
        $this->assertEquals('site/plugins/superplugin', $this->installer->getInstallPath($package));
        $this->installer->install(new InstalledArrayRepository(), $package);
        $this->assertFileExists($this->testDir . '/site/plugins/superplugin/index.php');
        $this->assertFileNotExists($this->testDir . '/site/plugins/superplugin/vendor-created.txt');
        $this->assertDirectoryNotExists($this->testDir . '/site/plugins/superplugin/vendor');
    }

    public function testInstallVendorDir()
    {
        $package = $this->pluginPackageFactory(self::SUPPORTED | self::VENDOR_DIR);
        $this->assertEquals('site/plugins/superplugin', $this->installer->getInstallPath($package));
        $this->installer->install(new InstalledArrayRepository(), $package);
        $this->assertFileExists($this->testDir . '/site/plugins/superplugin/index.php');
        $this->assertFileExists($this->testDir . '/site/plugins/superplugin/vendor-created.txt');
        $this->assertDirectoryNotExists($this->testDir . '/site/plugins/superplugin/vendor');
    }

    public function testUpdateNoSupport()
    {
        $repo = new InstalledArrayRepository();

        $initial = $this->pluginPackageFactory(self::VENDOR_DIR);
        $this->assertEquals($this->testDir . '/vendor/superwoman/superplugin', $this->installer->getInstallPath($initial));
        $this->installer->install($repo, $initial);
        $repo->addPackage($initial);
        $this->assertFileExists($this->testDir . '/vendor/superwoman/superplugin/index.php');
        $this->assertFileExists($this->testDir . '/vendor/superwoman/superplugin/vendor-created.txt');
        $this->assertDirectoryExists($this->testDir . '/vendor/superwoman/superplugin/vendor');

        $this->filesystem->emptyDirectory($this->testDir . '/vendor/superwoman/superplugin');
        $this->assertFileNotExists($this->testDir . '/vendor/superwoman/superplugin/index.php');
        $this->assertFileNotExists($this->testDir . '/vendor/superwoman/superplugin/vendor-created.txt');
        $this->assertDirectoryNotExists($this->testDir . '/vendor/superwoman/superplugin/vendor');

        $target = $this->pluginPackageFactory(self::VENDOR_DIR);
        $this->assertEquals($this->testDir . '/vendor/superwoman/superplugin', $this->installer->getInstallPath($target));
        $this->installer->update($repo, $initial, $target);
        $this->assertFileExists($this->testDir . '/vendor/superwoman/superplugin/index.php');
        $this->assertFileExists($this->testDir . '/vendor/superwoman/superplugin/vendor-created.txt');
        $this->assertDirectoryExists($this->testDir . '/vendor/superwoman/superplugin/vendor');
    }

    public function testUpdateWithoutVendorDir()
    {
        $repo = new InstalledArrayRepository();

        $initial = $this->pluginPackageFactory(self::SUPPORTED);
        $this->assertEquals('site/plugins/superplugin', $this->installer->getInstallPath($initial));
        $this->installer->install($repo, $initial);
        $repo->addPackage($initial);
        $this->assertFileExists($this->testDir . '/site/plugins/superplugin/index.php');
        $this->assertFileNotExists($this->testDir . '/site/plugins/superplugin/vendor-created.txt');
        $this->assertDirectoryNotExists($this->testDir . '/site/plugins/superplugin/vendor');

        $this->filesystem->emptyDirectory($this->testDir . '/site/plugins/superplugin');
        $this->assertFileNotExists($this->testDir . '/site/plugins/superplugin/index.php');
        $this->assertFileNotExists($this->testDir . '/site/plugins/superplugin/vendor-created.txt');
        $this->assertDirectoryNotExists($this->testDir . '/site/plugins/superplugin/vendor');

        $target = $this->pluginPackageFactory(self::SUPPORTED);
        $this->assertEquals('site/plugins/superplugin', $this->installer->getInstallPath($target));
        $this->installer->update($repo, $initial, $target);
        $this->assertFileExists($this->testDir . '/site/plugins/superplugin/index.php');
        $this->assertFileNotExists($this->testDir . '/site/plugins/superplugin/vendor-created.txt');
        $this->assertDirectoryNotExists($this->testDir . '/site/plugins/superplugin/vendor');
    }

    public function testUpdateVendorDir()
    {
        $repo = new InstalledArrayRepository();

        $initial = $this->pluginPackageFactory(self::SUPPORTED | self::VENDOR_DIR);
        $this->assertEquals('site/plugins/superplugin', $this->installer->getInstallPath($initial));
        $this->installer->install($repo, $initial);
        $repo->addPackage($initial);
        $this->assertFileExists($this->testDir . '/site/plugins/superplugin/index.php');
        $this->assertFileExists($this->testDir . '/site/plugins/superplugin/vendor-created.txt');
        $this->assertDirectoryNotExists($this->testDir . '/site/plugins/superplugin/vendor');

        $this->filesystem->emptyDirectory($this->testDir . '/site/plugins/superplugin');
        $this->assertFileNotExists($this->testDir . '/site/plugins/superplugin/index.php');
        $this->assertFileNotExists($this->testDir . '/site/plugins/superplugin/vendor-created.txt');
        $this->assertDirectoryNotExists($this->testDir . '/site/plugins/superplugin/vendor');

        $target = $this->pluginPackageFactory(self::SUPPORTED | self::VENDOR_DIR);
        $this->assertEquals('site/plugins/superplugin', $this->installer->getInstallPath($target));
        $this->installer->update($repo, $initial, $target);
        $this->assertFileExists($this->testDir . '/site/plugins/superplugin/index.php');
        $this->assertFileExists($this->testDir . '/site/plugins/superplugin/vendor-created.txt');
        $this->assertDirectoryNotExists($this->testDir . '/site/plugins/superplugin/vendor');
    }

    /**
     * Creates a dummy plugin package
     *
     * @param int $flags Combination of self::SUPPORTED and self::VENDOR_DIR
     * @param string $name Custom package name of the plugin package
     * @return Package
     */
    protected function pluginPackageFactory(int $flags = 0, string $name = 'superwoman/superplugin'): Package
    {
        $package = new Package($name, '1.0.0.0', '1.0.0');
        $package->setType('kirby-plugin');
        $package->setInstallationSource('dist');
        $package->setDistType('mock');

        if ($flags & self::SUPPORTED) {
            $package->setRequires([
                new Link('superwoman/superplugin', 'getkirby/composer-installer')
            ]);
        }

        if ($flags & self::VENDOR_DIR) {
            $package->setExtra([
                'with-vendor-dir' => true
            ]);
        }

        return $package;
    }
}
