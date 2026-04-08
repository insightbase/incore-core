<?php

declare(strict_types=1);

use App\Core\Logger\DiscordLogger;
use App\UI\Admin\Setting\SettingFacade;
use Nette\Bootstrap\Configurator;
use Nette\DI\Container;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Tracy\Debugger;

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
        $this->configurator->setTimeZone('Europe/Prague');

        $this->configurator->createRobotLoader()
            ->addDirectory(__DIR__)
            ->register()
        ;

        $dir = (dirname(__FILE__));
        if(str_contains($dir, '/vendor/')){
            $webhookErrorLogUrlFile = $dir.'/../../../../../private/';
        }else{
            $webhookErrorLogUrlFile = $dir.'/../../../incore-app/private/';
        }
        $webhookErrorLogUrlFile .= SettingFacade::DISCORD_ERROR_LOG_URL;

        if (Debugger::$productionMode && file_exists($webhookErrorLogUrlFile)) {
            $webhook = FileSystem::read($webhookErrorLogUrlFile);

            $logger = Debugger::getLogger();
            Debugger::setLogger(new DiscordLogger($logger, $webhook));
        }
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
        if (file_exists($this->rootDir.'/config/database.neon')) {
            $this->configurator->addConfig($configDir . '/database.neon');
        }
        if (file_exists($this->rootDir.'/config/local.neon')) {
            $this->configurator->addConfig($configDir.'/local.neon');
        }
    }
}
