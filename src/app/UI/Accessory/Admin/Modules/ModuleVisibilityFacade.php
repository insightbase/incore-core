<?php

namespace App\UI\Accessory\Admin\Modules;

use App\Model\Entity\ModuleEntity;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\DI\Container;

final class ModuleVisibilityFacade
{
    /**
     * @var VisibilityVoter[]
     */
    private array $voters = [];

    public function __construct(
        private readonly Container $container,
    )
    {
        $reflection = new \ReflectionClass($this->container);
        $property = $reflection->getProperty('wiring');
        $property->setAccessible(true);
        $services = $property->getValue($this->container);
        foreach (array_keys($services) as $class) {
            $reflection = new \ReflectionClass($class);
            if (array_key_exists(VisibilityVoter::class, $reflection->getInterfaces())) {
                $this->voters[] = $this->container->getByType($class);
            }
        }
    }

    public function isVisible(ActiveRow $module):bool
    {
        foreach($this->voters as $voter){
            if(!$voter->isVisible($module)){
                return false;
            }
        }
        return true;
    }

    /**
     * @param Selection<ModuleEntity> $modules
     * @return ModuleEntity[]
     */
    public function filter(Selection $modules):array
    {
        return array_values(array_filter($modules->fetchAll(), fn(ActiveRow $module) => $this->isVisible($module)));
    }
}