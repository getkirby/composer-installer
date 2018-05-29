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
class Installer extends LibraryInstaller
{
    // List of installation paths
    private static $installations = [];

    /**
     * Decides if the installer supports the given type
     *
     * @param  string $packageType
     * @return bool
     */
    public function supports($packageType): bool
    {
        return in_array($packageType, ['kirby-cms', 'kirby-panel']);
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

        // determine the path based on the type of the package we need to install
        switch ($package->getType()) {
            case 'kirby-cms':
                $path = $extra['kirby-cms-path'] ?? 'kirby';
                break;
            case 'kirby-panel':
                $path = $extra['kirby-panel-path'] ?? 'panel';
                break;
            default:
                throw new InvalidArgumentException('Unsupported package type ' . $package->getType() . '.');
        }

        // don't allow unsafe directories
        $vendorDir = $this->composer->getConfig()->get('vendor-dir', Config::RELATIVE_PATHS) ?? 'vendor';
        if ($path === $vendorDir || $path === '.') {
            throw new InvalidArgumentException('The path ' . $path . ' is an unsafe installation directory for ' . $package->getPrettyName() . '.');
        }

        // don't allow installation of multiple packages to the same directory
        if (isset(static::$installations[$path]) && static::$installations[$path] !== $package->getPrettyName()) {
            throw new InvalidArgumentException(
                'The path ' . $path . ' is already in use by package ' .
                static::$installations[$path] . ', cannot install package ' .
                $package->getPrettyName() . ' to same location.'
            );
        }
        static::$installations[$path] = $package->getPrettyName();

        return $path;
    }
}
