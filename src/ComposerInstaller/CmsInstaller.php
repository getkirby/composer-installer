<?php

namespace Kirby\ComposerInstaller;

use InvalidArgumentException;
use Composer\Config;
use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;

/**
 * @package   Kirby Composer Installer
 * @author    Lukas Bestle <lukas@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   MIT
 */
class CmsInstaller extends LibraryInstaller
{
    /**
     * Decides if the installer supports the given type
     *
     * @param  string $packageType
     * @return bool
     */
    public function supports($packageType): bool
    {
        return $packageType === 'kirby-cms';
    }

    /**
     * Returns the installation path of a package
     *
     * @param  PackageInterface $package
     * @return string           path
     */
    public function getInstallPath(PackageInterface $package): string
    {
        // get the extra configuration of the top-level package
        if ($rootPackage = $this->composer->getPackage()) {
            $extra = $rootPackage->getExtra();
        } else {
            $extra = [];
        }

        // use path from configuration, otherwise fall back to default
        $path = $extra['kirby-cms-path'] ?? 'kirby';

        // don't allow unsafe directories
        $vendorDir = $this->composer->getConfig()->get('vendor-dir', Config::RELATIVE_PATHS) ?? 'vendor';
        if ($path === $vendorDir || $path === '.') {
            throw new InvalidArgumentException('The path ' . $path . ' is an unsafe installation directory for ' . $package->getPrettyName() . '.');
        }

        return $path;
    }

    /**
     * Method override from the Composer LibraryInstaller;
     * run when the CMS code is being installed or updated
     *
     * @param PackageInterface $package
     */
    protected function installCode(PackageInterface $package)
    {
        // first install the CMS normally
        parent::installCode($package);

        // remove the CMS' `vendor` directory to avoid duplicated autoloader and vendor code
        $packageVendorDir = $this->getPackageBasePath($package) . '/vendor';
        if (is_dir($packageVendorDir)) {
            $success = $this->filesystem->removeDirectory($packageVendorDir);
            if (!$success) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Could not completely delete ' . $path . ', aborting.');
                // @codeCoverageIgnoreEnd
            }
        }
    }
}
