<?php

namespace App\Console;

use App\Component\Translator\Translator;
use App\Console\Exception\DatabaseUpdateException;
use App\Console\Exception\NoEntitiesFoundException;
use App\Core\DbParameterBag;
use App\Model\DoctrineEntity\NoGenerateTable;
use App\Service\Admin\GenerateEntitiesFacade;
use App\UI\Accessory\ParameterBag;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\DI\Container;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'db:update', description: 'Create/update tables')]
class GenerateDBCommand extends Command
{
    public function __construct(
        private readonly DbParameterBag         $dbParameterBag,
        private readonly Container              $container,
        private readonly Storage                $storage,
        private readonly GenerateEntitiesFacade $generateEntitiesFacade,
        private readonly ParameterBag           $parameterBag,
    ) {
        parent::__construct();
    }

    /**
     * @param OutputInterface $output
     * @return void
     * @throws NoEntitiesFoundException
     * @throws \ReflectionException|DatabaseUpdateException
     */
    private function updateTables(OutputInterface $output):void{
        $output->writeln('<info>Update tables</info>');
        $reflection = new \ReflectionClass($this->container);
        $property = $reflection->getProperty('wiring');
        $property->setAccessible(true);
        $services = $property->getValue($this->container);

        $classes = [];
        foreach (array_keys($services) as $class) {
            $reflection = new \ReflectionClass($class);
            if ($reflection->getAttributes(Table::class)) {
                if(!array_key_exists(NoGenerateTable::class, $reflection->getInterfaces())) {
                    $classes[] = $class;
                }
            }
        }

        $isDevMode = true;
        $dbParams = [
            'host' => $this->dbParameterBag->host,
            'dbname' => $this->dbParameterBag->dbname,
            'user' => $this->dbParameterBag->user,
            'password' => $this->dbParameterBag->password,
            'driver' => 'pdo_mysql',
        ];
        $config = ORMSetup::createConfiguration($isDevMode);

        $driverChain = new MappingDriverChain();
        foreach ($classes as $class) {
            $driver = new AttributeDriver([dirname((new \ReflectionClass($class))->getFileName())]);
            $driverChain->addDriver($driver, $class);
        }
        $config->setMetadataDriverImpl($driverChain);

        $connection = DriverManager::getConnection($dbParams, $config);
        $entityManager = new EntityManager($connection, $config);

        $metaData = $entityManager->getMetadataFactory()->getAllMetadata();
        if (empty($metaData)) {
            $output->writeln(sprintf('<error>No entities found in dir %s.</error>', $paths[0]));

            throw new NoEntitiesFoundException();
        }

        // Použití SchemaTool pro vytvoření schématu
        $schemaTool = new SchemaTool($entityManager);

        try {
            $schemaTool->updateSchema($metaData);
            $output->writeln('<info>Database updated.</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>Error: '.$e->getMessage().'</error>');

            throw new DatabaseUpdateException();
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {

            $this->updateTables($output);
            $this->saveFixtures($output);
        } catch (NoEntitiesFoundException|\ReflectionException|DatabaseUpdateException $e) {
            return self::FAILURE;
        }
        return self::SUCCESS;
    }

    /**
     * @param OutputInterface $output
     * @return void
     * @throws NoEntitiesFoundException
     * @throws \ReflectionException
     */
    private function saveFixtures(OutputInterface $output):void
    {
        $output->writeln('<info>Save fixtures</info>');

        $reflection = new \ReflectionClass($this->container);
        $property = $reflection->getProperty('wiring');
        $property->setAccessible(true);
        $services = $property->getValue($this->container);

        $classes = $fixtureFiles = [];
        foreach (array_keys($services) as $class) {
            $reflection = new \ReflectionClass($class);
            if ($reflection->getAttributes(Table::class)) {
                $classes[] = $class;
            }
            if (array_key_exists(FixtureInterface::class, $reflection->getInterfaces())) {
                $fixtureFiles[] = $reflection->getFileName();
            }
        }

        $isDevMode = true;
        $dbParams = [
            'host' => $this->dbParameterBag->host,
            'dbname' => $this->dbParameterBag->dbname,
            'user' => $this->dbParameterBag->user,
            'password' => $this->dbParameterBag->password,
            'driver' => 'pdo_mysql',
        ];
        $config = ORMSetup::createConfiguration($isDevMode);

        $driverChain = new MappingDriverChain();
        foreach ($classes as $class) {
            $driver = new AttributeDriver([dirname((new \ReflectionClass($class))->getFileName())]);
            $driverChain->addDriver($driver, $class);
        }
        $config->setMetadataDriverImpl($driverChain);

        $connection = DriverManager::getConnection($dbParams, $config);
        $entityManager = new EntityManager($connection, $config);

        $metaData = $entityManager->getMetadataFactory()->getAllMetadata();
        if (empty($metaData)) {
            $output->writeln(sprintf('<error>No entities found in dir %s.</error>', $paths[0]));

            throw new NoEntitiesFoundException();
        }

        $loader = new Loader();
        foreach ($fixtureFiles as $fixtureFile) {
            $loader->loadFromFile($fixtureFile);
        }
        $executor = new ORMExecutor($entityManager);
        $executor->execute($loader->getFixtures(), true);

        $cache = new Cache($this->storage, Translator::CACHE_NAMESPACE);
        $cache->clean([$cache::All => true]);

        if($this->parameterBag->autoGenerateEntities) {
            $this->generateEntitiesFacade->generate($output);
        }
    }
}
