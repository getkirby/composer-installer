<?php

namespace Kirby\ComposerInstaller;

use InvalidArgumentException;
use RuntimeException;
use Composer\Config;
use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;

/**
 * @package   Kirby Composer Installer
 * @author    Lukas Bestle <lukas@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   MIT
 */
class PluginInstaller extends LibraryInstaller
{
    /**
     * Decides if the installer supports the given type
     *
     * @param  string $packageType
     * @return bool
     */
    public function supports($packageType): bool
    {
        return $packageType === 'kirby-plugin';
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

        // use base path from configuration, otherwise fall back to default
        $basePath = $extra['kirby-plugin-path'] ?? 'site/plugins';

        // determine the plugin name from its package name;
        // can be overridden in the plugin's `composer.json`
        $prettyName = $package->getPrettyName();
        $pluginExtra = $package->getExtra();
        if (!empty($pluginExtra['installer-name'])) {
            $name = $pluginExtra['installer-name'];
        } elseif (strpos($prettyName, '/') !== false) {
            // use name after the slash
            $name = explode('/', $prettyName)[1];
        } else {
            $name = $prettyName;
        }

        // build destination path from base path and plugin name
        return $basePath . '/' . $name;
    }

    /**
     * Installs specific package.
     *
     * @param InstalledRepositoryInterface $repo    repository in which to check
     * @param PackageInterface             $package package instance
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        // first install the plugin normally
        parent::install($repo, $package);

        // remove its `vendor` directory to avoid duplicated autoloader and vendor code
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
