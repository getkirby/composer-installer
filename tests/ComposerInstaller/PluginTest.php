<?php

namespace Kirby\ComposerInstaller;

use Composer\Composer;
use Composer\Config;
use Composer\Installer\InstallationManager;
use Composer\IO\NullIO;
use Composer\Plugin\PluginInterface;
use Composer\Util\HttpDownloader;
use Composer\Util\Loop;
use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    public function testActivate()
    {
        $config   = new Config();
        $io       = new NullIO();
        $composer = new Composer();

        if (version_compare(PluginInterface::PLUGIN_API_VERSION, '2.0.0', '<') === true) {
            $installationManager = new InstallationManager();
        } else {
            $httpDownloader      = new HttpDownloader($io, $config);
            $loop                = new Loop($httpDownloader);
            $installationManager = new InstallationManager($loop, $io);
        }

        $composer->setInstallationManager($installationManager);
        $composer->setConfig($config);

        $plugin = new Plugin();
        $plugin->activate($composer, $io);

        $installer = $installationManager->getInstaller('kirby-cms');
        $this->assertInstanceOf(CmsInstaller::class, $installer);

        $installer = $installationManager->getInstaller('kirby-plugin');
        $this->assertInstanceOf(PluginInstaller::class, $installer);
    }
}
