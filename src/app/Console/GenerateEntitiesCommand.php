<?php

namespace App\Console;

use App\Service\GenerateEntitiesFacade;
use Nette\PhpGenerator\PhpNamespace;
use Nette\Utils\DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'db:generateEntity', description: 'Regenerate entities')]
class GenerateEntitiesCommand extends Command
{
    public function __construct(
        private readonly GenerateEntitiesFacade $generateEntitiesFacade,
    ) {
        parent::__construct(null);
    }

    public function getType(string $type, PhpNamespace $nameSpace): string
    {
        if ('DateTimeImmutable' === $type) {
            $nameSpace->addUse(DateTime::class);

            return 'DateTime';
        }
        if (str_contains($type, 'DoctrineEntity')) {
            $namespace = explode('\\', $type);

            return $namespace[count($namespace) - 1].'Entity';
        }

        return $type;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->generateEntitiesFacade->generate($output);

        return self::SUCCESS;
    }
}
