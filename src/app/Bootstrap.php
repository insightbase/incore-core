<?php

declare(strict_types=1);

use Nette\Bootstrap\Configurator;

class Bootstrap
{
    protected Configurator $configurator;
    protected string $rootDir;

    public function bootCLIApplication(): Nette\DI\Container
    {
        $this->initializeEnvironment();
        $this->setupContainer();
        return $this->configurator->createContainer();
    }


    public function bootWebApplication(): Nette\DI\Container
    {
        $this->initializeEnvironment();
        $this->setupContainer();
        return $this->configurator->createContainer();
    }


    public function initializeEnvironment(): void
    {
        if(file_exists($this->rootDir . '/config/local.neon')){
            $this->configurator->setDebugMode(true);
        }else{
            $this->configurator->setDebugMode('inCORE@' . ($_SERVER['REMOTE_ADDR'] ?? php_uname('n')));
        }
        $this->configurator->enableTracy($this->rootDir . '/log');

        $this->configurator->createRobotLoader()
            ->addDirectory(__DIR__)
            ->register();
    }


    protected function setupContainer(): void
    {
        $configDir = $this->rootDir . '/config';

        $vendorIncoreDir = $this->rootDir . '/vendor/incore/';

        foreach(\Nette\Utils\Finder::findDirectories('*')->in($vendorIncoreDir) as $incoreModule){
            if(is_dir($incoreModule->getPathname() . '/src/config')) {
                foreach (Nette\Utils\Finder::findFiles(['*.neon'])->from($incoreModule->getPathname() . '/src/config') as $name => $file) {
                    $this->configurator->addConfig($name);
                }
            }
        }

        $this->configurator->addConfig($configDir . '/common.neon');
        $this->configurator->addConfig($configDir . '/services.neon');
        if(file_exists($this->rootDir . '/config/local.neon')){
            $this->configurator->addConfig($configDir . '/local.neon');
        }
    }
}
