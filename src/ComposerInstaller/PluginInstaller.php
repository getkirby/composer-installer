<?php

namespace Kirby\ComposerInstaller;

use InvalidArgumentException;
use RuntimeException;
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
        // place into `vendor` directory as usual if Pluginkit is not supported
        if (!$this->supportsPluginkit($package)) {
            return parent::getInstallPath($package);
        }

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
     * Method override from the Composer LibraryInstaller;
     * run when the plugin's code is being installed or updated
     *
     * @param PackageInterface $package
     */
    protected function installCode(PackageInterface $package)
    {
        // first install the plugin normally
        parent::installCode($package);

        // only continue if Pluginkit is supported
        if (!$this->supportsPluginkit($package)) {
            return;
        }

        // remove the plugin's `vendor` directory to avoid duplicated autoloader and vendor code
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

    /**
     * Checks if the package has explicitly required this installer;
     * otherwise the installer will fall back to the behavior of the LibraryInstaller
     * (Pluginkit is not yet supported by the plugin)
     *
     * @param  PackageInterface $package
     * @return bool
     */
    protected function supportsPluginkit(PackageInterface $package): bool
    {
        foreach ($package->getRequires() as $link) {
            if ($link->getTarget() === 'getkirby/composer-installer') {
                return true;
            }
        }

        // no required package is the installer
        return false;
    }
}
