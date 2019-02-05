<?php

namespace Kirby\ComposerInstaller;

use PHPUnit\Framework\TestCase;

use Composer\Composer;
use Composer\Config;
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
        // change to the test dir
        $this->testDir = dirname(__DIR__) . '/tmp';
        if (!is_dir($this->testDir)) {
            mkdir($this->testDir);
        }
        chdir($this->testDir);

        // initialize new Composer instance
        $this->io = new NullIO();
        $config = new Config();
        $config->merge([
            'config' => [
                'vendor-dir' => $this->testDir . '/vendor'
            ]
        ]);
        $this->filesystem = new Filesystem();
        $this->composer = new Composer();
        $this->composer->setConfig($config);
        $this->composer->setDownloadManager(new MockDownloadManager($this->io, false, $this->filesystem));
    }

    public function tearDown()
    {
        $this->filesystem->removeDirectory($this->testDir);
    }
}
