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
        private readonly ParameterBag $parameterBag,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $wwwDir = $this->parameterBag->rootDir . '/www';
        $assetsDir = dirname(__FILE__) . '/../../assets';

        $output->writeln('<info>Metronic...</info>');
        FileSystem::delete($wwwDir . '/' . $this->parameterBag->metronicDir);
        FileSystem::copy($assetsDir . '/metronic', $wwwDir . '/' . $this->parameterBag->metronicDir);
        $output->writeln('<info>inCORE...</info>');
        FileSystem::delete($wwwDir . '/incore');
        FileSystem::createDir($wwwDir . '/incore/public');
        FileSystem::copy($assetsDir . '/incore', $wwwDir . '/incore');
        FileSystem::copy($assetsDir . '/admin/public', $wwwDir . '/incore/public');

        return self::SUCCESS;


    }
}