<?php

namespace Kirby\ComposerInstaller;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

/**
 * @package   Kirby Composer Installer
 * @author    Lukas Bestle <lukas@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   MIT
 */
class Plugin implements PluginInterface
{
    /**
     * Apply plugin modifications to Composer
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $cmsInstaller = new CmsInstaller($io, $composer);
        $composer->getInstallationManager()->addInstaller($cmsInstaller);

        $pluginInstaller = new PluginInstaller($io, $composer);
        $composer->getInstallationManager()->addInstaller($pluginInstaller);
    }
}
