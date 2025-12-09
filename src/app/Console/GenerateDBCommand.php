<?php

namespace App\Console;

use App\Console\Exception\DatabaseUpdateException;
use App\Console\Exception\NoEntitiesFoundException;
use App\Service\Admin\GenerateDbFacade;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'db:update', description: 'Create/update tables')]
class GenerateDBCommand extends Command
{
    public function __construct(
        private readonly GenerateDbFacade       $generateDbFacade,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->generateDbFacade->updateTable($output);
            $this->generateDbFacade->saveFixtures($output);
        } catch (NoEntitiesFoundException|\ReflectionException|DatabaseUpdateException $e) {
            return self::FAILURE;
        }
        return self::SUCCESS;
    }
}
