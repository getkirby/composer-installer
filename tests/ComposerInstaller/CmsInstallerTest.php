<?php

namespace Kirby\ComposerInstaller;

use Composer\Package\Package;
use Composer\Repository\InstalledArrayRepository;

class CmsInstallerTest extends InstallerTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->installer = new CmsInstaller($this->io, $this->composer);
    }

    public function testGetInstallPathCustomPath()
    {
        $this->initRootPackage()->setExtra([
            'kirby-cms-path' => 'cms'
        ]);

        $package = $this->cmsPackageFactory();
        $this->assertEquals('cms', $this->installer->getInstallPath($package));
    }

    public function testGetInstallPathDefault()
    {
        $package = $this->cmsPackageFactory();
        $this->assertEquals('kirby', $this->installer->getInstallPath($package));
    }

    public function testGetInstallPathUnsafe1()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The path . is an unsafe installation directory for getkirby/cms.');

        $this->initRootPackage()->setExtra([
            'kirby-cms-path' => '.'
        ]);

        $package = $this->cmsPackageFactory();
        $this->installer->getInstallPath($package);
    }

    public function testGetInstallPathUnsafe2()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The path vendor is an unsafe installation directory for getkirby/cms.');

        $this->initRootPackage()->setExtra([
            'kirby-cms-path' => 'vendor'
        ]);

        $package = $this->cmsPackageFactory();
        $this->installer->getInstallPath($package);
    }

    public function testGetInstallPathUnsafe3()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The path custom-vendor is an unsafe installation directory for getkirby/cms.');

        $this->initRootPackage()->setExtra([
            'kirby-cms-path' => 'custom-vendor'
        ]);

        $package = $this->cmsPackageFactory();
        $this->assertEquals('custom-vendor', $this->installer->getInstallPath($package));

        $this->composer->getConfig()->merge([
            'config' => [
                'vendor-dir' => 'custom-vendor'
            ]
        ]);

        $package = $this->cmsPackageFactory();
        $this->installer->getInstallPath($package);
    }

    public function testGetInstallPathVendor()
    {
        $this->initRootPackage()->setExtra([
            'kirby-cms-path' => false
        ]);

        $package = $this->cmsPackageFactory();
        $this->assertEquals($this->testDir . '/vendor/getkirby/cms', $this->installer->getInstallPath($package));
    }

    public function testInstall()
    {
        $package = $this->cmsPackageFactory();
        $this->assertEquals('kirby', $this->installer->getInstallPath($package));
        $this->installer->install(new InstalledArrayRepository(), $package);
        $this->assertFileExists($this->testDir . '/kirby/index.php');
        $this->assertFileExists($this->testDir . '/kirby/vendor-created.txt');
        $this->assertDirectoryDoesNotExist($this->testDir . '/kirby/vendor');
    }

    public function testInstallVendor()
    {
        $this->initRootPackage()->setExtra([
            'kirby-cms-path' => false
        ]);

        $package = $this->cmsPackageFactory();
        $this->assertEquals($this->testDir . '/vendor/getkirby/cms', $this->installer->getInstallPath($package));
        $this->installer->install(new InstalledArrayRepository(), $package);
        $this->assertFileExists($this->testDir . '/vendor/getkirby/cms/index.php');
        $this->assertFileExists($this->testDir . '/vendor/getkirby/cms/vendor-created.txt');
        $this->assertDirectoryDoesNotExist($this->testDir . '/vendor/getkirby/cms/vendor');
    }

    public function testSupports()
    {
        $this->assertTrue($this->installer->supports('kirby-cms'));
        $this->assertFalse($this->installer->supports('kirby-plugin'));
        $this->assertFalse($this->installer->supports('amazing-cms'));
    }

    public function testUpdate()
    {
        $repo = new InstalledArrayRepository();

        $initial = $this->cmsPackageFactory();
        $this->assertEquals('kirby', $this->installer->getInstallPath($initial));
        $this->installer->install($repo, $initial);
        $repo->addPackage($initial);
        $this->assertFileExists($this->testDir . '/kirby/index.php');
        $this->assertFileExists($this->testDir . '/kirby/vendor-created.txt');
        $this->assertDirectoryDoesNotExist($this->testDir . '/kirby/vendor');

        $this->filesystem->emptyDirectory($this->testDir . '/kirby');
        $this->assertFileDoesNotExist($this->testDir . '/kirby/index.php');
        $this->assertFileDoesNotExist($this->testDir . '/kirby/vendor-created.txt');
        $this->assertDirectoryDoesNotExist($this->testDir . '/kirby/vendor');

        $target = $this->cmsPackageFactory();
        $this->assertEquals('kirby', $this->installer->getInstallPath($target));
        $this->installer->update($repo, $initial, $target);
        $this->assertFileExists($this->testDir . '/kirby/index.php');
        $this->assertFileExists($this->testDir . '/kirby/vendor-created.txt');
        $this->assertDirectoryDoesNotExist($this->testDir . '/kirby/vendor');
    }

    /**
     * Creates a dummy CMS package
     *
     * @return Package
     */
    protected function cmsPackageFactory(): Package
    {
        $package = new Package('getkirby/cms', '1.0.0.0', '1.0.0');
        $package->setType('kirby-cms');
        $package->setInstallationSource('dist');
        $package->setDistType('mock');
        $package->setExtra([
            'with-vendor-dir' => true // tell the MockDownloader to create a `vendor` dir
        ]);

        return $package;
    }
}
