<?php

declare(strict_types=1);

use Nette\Bootstrap\Configurator;
use Nette\DI\Container;
use Nette\Utils\Finder;

class Bootstrap
{
    protected Configurator $configurator;
    protected string $rootDir;

    public function bootCLIApplication(): Container
    {
        $this->initializeEnvironment();
        $this->setupContainer();

        return $this->configurator->createContainer();
    }

    public function bootWebApplication(): Container
    {
        $this->initializeEnvironment();
        $this->setupContainer();

        return $this->configurator->createContainer();
    }

    public function initializeEnvironment(): void
    {
        if (file_exists($this->rootDir.'/config/local.neon')) {
            $this->configurator->setDebugMode(true);
        } else {
            $this->configurator->setDebugMode('inCORE@'.($_SERVER['REMOTE_ADDR'] ?? php_uname('n')));
        }
        $this->configurator->enableTracy($this->rootDir.'/log');

        $this->configurator->createRobotLoader()
            ->addDirectory(__DIR__)
            ->register()
        ;
    }

    protected function setupContainer(): void
    {
        $configDir = $this->rootDir.'/config';

        $vendorIncoreDir = $this->rootDir.'/vendor/incore/';

        foreach (Finder::findDirectories('*')->in($vendorIncoreDir) as $incoreModule) {
            if (is_dir($incoreModule->getPathname().'/src/config')) {
                foreach (Finder::findFiles(['*.neon'])->from($incoreModule->getPathname().'/src/config') as $name => $file) {
                    $this->configurator->addConfig($name);
                }
            }
        }

        $this->configurator->addConfig($configDir.'/common.neon');
        $this->configurator->addConfig($configDir.'/services.neon');
        if (file_exists($this->rootDir.'/config/local.neon')) {
            $this->configurator->addConfig($configDir . '/database.neon');
        }
        if (file_exists($this->rootDir.'/config/local.neon')) {
            $this->configurator->addConfig($configDir.'/local.neon');
        }
    }
}
