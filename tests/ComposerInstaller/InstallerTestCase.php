<?php

namespace Kirby\ComposerInstaller;

use PHPUnit\Framework\TestCase;

use Composer\Composer;
use Composer\Config;
use Composer\Downloader\DownloadManager;
use Composer\IO\NullIO;
use Composer\Util\Filesystem;

class InstallerTestCase extends TestCase
{
    protected $testDir;
    protected $io;
    protected $composer;
    protected $filesystem;
    protected $installer;

    public function setUp()
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
        if (!is_dir($this->testDir)) {
            $this->filesystem->ensureDirectoryExists($this->testDir);
        }
        chdir($this->testDir);
    }

    public function tearDown()
    {
        $this->filesystem->removeDirectory($this->testDir);
    }
}
