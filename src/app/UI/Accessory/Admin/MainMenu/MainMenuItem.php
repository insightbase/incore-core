<?php

namespace App\UI\Accessory\Admin\MainMenu;

use App\Model\Entity\ModuleEntity;
use Nette\Database\Table\ActiveRow;
use Nette\Security\User;

class MainMenuItem
{
    /**
     * @var array
     */
    private array $params = [];

    /**
     * @var MainMenuSub[]
     */
    private array $subs = [];

    /**
     * @param ModuleEntity $module
     * @param string $action
     * @param string $title
     * @param MainMenuSubFactory $mainMenuSubFactory
     */
    public function __construct(
        private readonly ActiveRow          $module,
        private readonly string             $action,
        private readonly string             $title,
        private readonly MainMenuSubFactory $mainMenuSubFactory,
        private readonly User               $userSecurity,
    )
    {
    }

    public function addSub(string $action,string $title,array $params):MainMenuSub
    {
        $this->subs[] = $sub = $this->mainMenuSubFactory->create($action,$title,$params);
        return $sub;
    }

    public function addParam(string $param, ?string $value): self
    {
        $this->params[$param] = $value;
        return $this;
    }

    /**
     * @return ModuleEntity
     */
    public function getModule(): ActiveRow
    {
        return $this->module;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @return MainMenuSub[]
     */
    public function getSubs(): array
    {
        $subs = [];
        foreach($this->subs as $mainMenuSub){
            if($this->userSecurity->isAllowed($this->module->system_name, $mainMenuSub->getAction())){
                $subs[] = $mainMenuSub;
            }
        }
        return $subs;
    }
}