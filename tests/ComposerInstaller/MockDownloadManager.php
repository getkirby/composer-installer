<?php

namespace Kirby\ComposerInstaller;

use Composer\Downloader\DownloadManager;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;

class MockDownloadManager extends DownloadManager
{
    public function __construct(IOInterface $io, $preferSource = false, Filesystem $filesystem = null)
    {
        parent::__construct($io, $preferSource, $filesystem);

        // prepare the mock downloader
        $this->setDownloader('mock', new MockDownloader($filesystem));
    }
}
