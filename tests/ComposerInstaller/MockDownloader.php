<?php

namespace Kirby\ComposerInstaller;

use Composer\Downloader\DownloaderInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
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

    public function download(PackageInterface $package, $path, ?PackageInterface $prevPackage = null)
    {
        // Composer 1 did not have an install method, but only a download method
        if (version_compare(PluginInterface::PLUGIN_API_VERSION, '2.0.0', '<') === true) {
            $this->install($package, $path);
        }
    }

    public function prepare($type, PackageInterface $package, $path, ?PackageInterface $prevPackage = null)
    {
        // do nothing (not needed for testing)
    }

    public function install(PackageInterface $package, $path)
    {
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
        $this->install($target, $path);
    }

    public function remove(PackageInterface $package, $path)
    {
        $this->filesystem->remove($path);
    }

    public function cleanup($type, PackageInterface $package, $path, ?PackageInterface $prevPackage = null)
    {
        // do nothing (not needed for testing)
    }

    public function setOutputProgress($outputProgress)
    {
        // we don't care about that shit
        // only relevant for Composer 1, was removed in Composer 2
    }
}
