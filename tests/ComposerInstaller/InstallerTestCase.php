<?php

namespace Kirby\ComposerInstaller;

use Composer\Composer;
use Composer\Config;
use Composer\Downloader\DownloadManager;
use Composer\IO\NullIO;
use Composer\Package\RootPackage;
use Composer\Util\Filesystem;
use PHPUnit\Framework\TestCase;

class InstallerTestCase extends TestCase
{
    protected $composer;
    protected $filesystem;
    protected $installer;
    protected $io;
    protected $rootPackage;
    protected $testDir;

    public function setUp(): void
    {
        $this->testDir = dirname(__DIR__) . '/tmp';

        // initialize new Composer instance
        $this->io = new NullIO();
        $this->filesystem = new Filesystem();
        $this->composer = new Composer();
        $this->composer->setConfig(new Config(false, $this->testDir));
        $downloadManager = new DownloadManager($this->io, false, $this->filesystem);
        $downloadManager->setDownloader('mock', new MockDownloader($this->filesystem));
        $this->composer->setDownloadManager($downloadManager);

        // initialize test dir and switch to it to make relative paths work
        $this->filesystem->ensureDirectoryExists($this->testDir);
        chdir($this->testDir);
    }

    public function tearDown(): void
    {
        $this->filesystem->removeDirectory($this->testDir);
    }

    /**
     * Initializes a root Kirby site package and returns it
     *
     * @return RootPackage
     */
    public function initRootPackage(): RootPackage
    {
        $rootPackage = new RootPackage('getkirby/amazing-site', '1.0.0.0', '1.0.0');
        $this->composer->setPackage($rootPackage);

        return $rootPackage;
    }
}
