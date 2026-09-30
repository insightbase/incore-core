<?php

namespace App\Console;

use App\Component\EditorJs\EditorJsJson;
use Nette\Database\Explorer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Jednorázový převod prázdných hodnot (`''` a EditorJs JSON bez bloků) na NULL.
 * Spouští se až po `db:update`, který sloupce převede na nullable. Tabulky
 * nenainstalovaných modulů se přeskočí.
 */
#[AsCommand(name: 'db:normalizeEmptyEditorJs', description: 'Convert empty EditorJs values to NULL')]
class NormalizeEmptyEditorJsCommand extends Command
{
    /** @var array<string, string> tabulka => sloupec */
    private const array COLUMNS = [
        'translate_language' => 'value',
        'static_page' => 'content',
        'static_page_language' => 'content',
        'content_block_item_text' => 'text',
        'content_field_value' => 'value',
        'content_field_value_language' => 'value',
        'enumeration_item_value' => 'value',
        'enumeration_item_value_language' => 'value',
    ];

    public function __construct(
        private readonly Explorer $explorer,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only count the rows, do not change them');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = (bool) $input->getOption('dry-run');
        $tables = $this->explorer->getConnection()->getDriver()->getTables();
        $tableNames = array_map(static fn(array $table): string => $table['name'], $tables);

        foreach (self::COLUMNS as $table => $column) {
            if (!in_array($table, $tableNames, true)) {
                $output->writeln(sprintf('<comment>%s: tabulka neexistuje, přeskočeno</comment>', $table));
                continue;
            }

            // Předvýběr v SQL, přesné posouzení (platný JSON s prázdnými bloky) v PHP.
            $ids = [];
            $values = $this->explorer->table($table)
                ->where("TRIM(?name) = '' OR ?name LIKE ?", $column, $column, '%"blocks":[]%')
                ->fetchPairs('id', $column);
            foreach ($values as $id => $value) {
                if (EditorJsJson::isEmpty($value)) {
                    $ids[] = $id;
                }
            }

            if ($ids !== [] && !$dryRun) {
                $this->explorer->table($table)->where('id', $ids)->update([$column => null]);
            }

            $output->writeln(sprintf(
                '<info>%s.%s: %d %s</info>',
                $table,
                $column,
                count($ids),
                $dryRun ? 'k převodu' : 'převedeno na NULL',
            ));
        }

        return self::SUCCESS;
    }
}
