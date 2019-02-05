<?php

namespace Kirby\ComposerInstaller;

use Composer\Downloader\DownloadManager;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
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

        // install a fake package directory
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
