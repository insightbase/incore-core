<?php

namespace App\Console;

use App\UI\Accessory\ParameterBag;
use Nette\Utils\FileSystem;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'assets:sync', description: 'Synchronize assets')]
class SyncAssetsCommand extends Command
{
    public function __construct(
        private ParameterBag $parameterBag,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $wwwDir = $this->parameterBag->rootDir . '/www';
        $assetsDir = dirname(__FILE__) . '/../../assets';

        $output->writeln('<info>Dist...</info>');
        FileSystem::delete($wwwDir . '/dist');
        FileSystem::copy($assetsDir . '/dist', $wwwDir . '/dist');
        $output->writeln('<info>inCORE...</info>');
        FileSystem::delete($wwwDir . '/incore');
        FileSystem::copy($assetsDir . '/incore', $wwwDir . '/incore');

        return self::SUCCESS;


    }
}