<?php

namespace Kirby\ComposerInstaller;

use Composer\Downloader\DownloaderInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;

class MockDownloader implements DownloaderInterface
{
    protected $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function getInstallationSource()
    {
        return 'dist';
    }

    public function download(PackageInterface $package, $path)
    {
        $path = dirname(__DIR__) . '/tmp/' . $path;

        // install a fake package directory
        $this->filesystem->ensureDirectoryExists($path);
        touch($path . '/index.php');

        // create a vendor dir if requested by the test
        if (!empty($package->getExtra()['with-vendor-dir'])) {
            $this->filesystem->ensureDirectoryExists($path . '/vendor/test');
            touch($path . '/vendor/test/test.txt');
            touch($path . '/vendor-created.txt');
        }
    }

    public function update(PackageInterface $initial, PackageInterface $target, $path)
    {
        $this->remove($initial, $path);
        $this->download($target, $path);
    }

    public function remove(PackageInterface $package, $path)
    {
        // do nothing (not needed for testing)
    }

    public function setOutputProgress($outputProgress)
    {
        // we don't care about that shit
    }
}
