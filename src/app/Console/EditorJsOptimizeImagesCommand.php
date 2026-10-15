<?php

declare(strict_types=1);

namespace App\Console;

use App\Component\EditorJs\EditorJsOptimizeImagesFacade;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'editor-js:optimize-images', description: 'Převede obrázky z editor.js na WebP max. FullHD a přepíše na ně odkazy v DB')]
class EditorJsOptimizeImagesCommand extends Command
{
    public function __construct(
        private readonly EditorJsOptimizeImagesFacade $editorJsOptimizeImagesFacade,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Jen vypíše, co by se převedlo');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->editorJsOptimizeImagesFacade->execute($output, (bool) $input->getOption('dry-run'));
        return self::SUCCESS;
    }
}
