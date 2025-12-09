<?php

namespace App\UI\Admin\FixDb;

use App\Console\Exception\DatabaseUpdateException;
use App\Console\Exception\NoEntitiesFoundException;
use App\Core\DbParameterBag;
use App\Model\DoctrineEntity\NoGenerateTable;
use App\Service\Admin\GenerateDbFacade;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use JetBrains\PhpStorm\NoReturn;
use Nette\Application\UI\Presenter;
use Nette\DI\Container;

class FixDbPresenter extends Presenter
{
    use StandardTemplateTrait;

    public function __construct(
        private readonly Container        $container,
        private readonly DbParameterBag   $dbParameterBag,
        private readonly GenerateDbFacade $generateDbFacade,
    )
    {
        parent::__construct();
    }

    #[NoReturn]
    public function actionDefault():void
    {
        $this->generateDbFacade->updateTable();
        $this->generateDbFacade->saveFixtures();
        echo('DONE');
        $this->terminate();
    }
}