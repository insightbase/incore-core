<?php

namespace App\UI\Accessory\MainMenu;

use App\Model\Entity\ModuleEntity;
use Nette\Database\Table\ActiveRow;
use Nette\DI\Container;
use Nette\Utils\Arrays;

class MainMenuFactory
{
    /**
     * @var MainMenuModule[]
     */
    private array $mainMenus = [];

    public function __construct(
        private readonly Container           $container,
    )
    {
        $reflection = new \ReflectionClass($this->container);
        $property = $reflection->getProperty('wiring');
        $property->setAccessible(true);
        $services = $property->getValue($this->container);
        $mainMenuFactories = [];
        foreach (array_keys($services) as $class) {
            $reflection = new \ReflectionClass($class);
            if (array_key_exists(MainMenuModule::class, $reflection->getInterfaces())) {
                $mainMenuFactories[] = $class;
            }
        }

        foreach($mainMenuFactories as $mainMenuFactoryClass){
            /** @var MainMenuModule $mainMenuFactory */
            $mainMenuFactory = $this->container->getByType($mainMenuFactoryClass);
            $this->mainMenus[] = $mainMenuFactory;
        }
    }

    /**
     * @param ModuleEntity $module
     * @return MainMenuModule[]
     */
    public function getMainMenus(ActiveRow $module): array
    {
        $ret = [];
        foreach($this->mainMenus as $mainMenu){
            if($mainMenu->getModule()->id === $module->id){
                $ret[] = $mainMenu;
            }
        }
        return $ret;
    }
}