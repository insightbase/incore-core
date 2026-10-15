<?php

declare(strict_types=1);

namespace App\Component\EditorJs;

use Nette\Database\Explorer;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dávkově převede už nahrané obrázky z editor.js na WebP max. FullHD,
 * přepíše odkazy v DB a smaže originály. Náhledy se negenerují - vzniknou
 * líně při prvním zobrazení.
 */
readonly class EditorJsOptimizeImagesFacade
{
    private const TextColumnTypes = ['char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'json'];

    public function __construct(
        private EditorJsImageFacade $editorJsImageFacade,
        private Explorer            $explorer,
    ) {}

    public function execute(OutputInterface $output, bool $dryRun): void
    {
        $dir = $this->editorJsImageFacade->getDir();
        if (!is_dir($dir)) {
            $output->writeln("<comment>Adresář {$dir} neexistuje.</comment>");
            return;
        }

        $output->writeln('<info>Optimalizace obrázků editor.js</info>' . ($dryRun ? ' <comment>(dry-run)</comment>' : ''));

        $columns = $this->getTextColumns();
        $converted = 0;
        $failed = 0;
        $bytesBefore = 0;
        $bytesAfter = 0;

        $paths = array_map(strval(...), iterator_to_array(Finder::findFiles('*')->in($dir), false));
        sort($paths);
        foreach ($paths as $path) {
            $name = basename($path);

            if (!$this->editorJsImageFacade->isOptimizable($path)) {
                continue;
            }

            if (!$this->editorJsImageFacade->needsOptimization($path)) {
                continue;
            }

            $webpName = $this->editorJsImageFacade->getWebpName($name);
            $newName = $webpName === $name ? $name : $this->editorJsImageFacade->getUniqueName($webpName);
            $sizeBefore = (int) filesize($path);
            $output->write("  převod  {$name} -> {$newName} (" . $this->formatBytes($sizeBefore));

            if ($dryRun) {
                $output->writeln(')');
                $converted++;
                continue;
            }

            try {
                $this->editorJsImageFacade->optimize($path, $newName);
            } catch (\Throwable $e) {
                $output->writeln(")\n    <error>{$e->getMessage()}</error>");
                $failed++;
                continue;
            }

            $sizeAfter = (int) filesize($dir . '/' . $newName);
            $output->write(' -> ' . $this->formatBytes($sizeAfter) . ')');

            // náhled a FullHD verze starého souboru už neplatí, nové vzniknou při prvním zobrazení
            $this->editorJsImageFacade->deleteDerivatives($name);

            if ($newName !== $name) {
                $rows = $this->replaceReferences($columns, $name, $newName);
                $output->write(", odkazů v DB: {$rows}");
                // originál mažeme až po přepsání odkazů - když DB selže, zůstane
                FileSystem::delete($path);
            }
            $output->writeln('');

            $converted++;
            $bytesBefore += $sizeBefore;
            $bytesAfter += $sizeAfter;
        }

        $output->writeln('');
        $output->writeln("  Převedeno: {$converted}, chyby: {$failed}");
        if (!$dryRun && $converted > 0) {
            $output->writeln('  Velikost: ' . $this->formatBytes($bytesBefore) . ' -> ' . $this->formatBytes($bytesAfter));
        }
    }

    /**
     * Všechny textové sloupce v DB, kde se vyskytuje odkaz na editor-js -
     * obsah editoru je v různých tabulkách (akce, zprávy, soutěže, výčty...).
     *
     * @return list<array{table: string, column: string}>
     */
    private function getTextColumns(): array
    {
        $columns = [];
        $rows = $this->explorer->query(
            'SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND DATA_TYPE IN (?)',
            self::TextColumnTypes,
        );
        foreach ($rows as $row) {
            $exists = $this->explorer->query(
                'SELECT 1 FROM ?name WHERE ?name LIKE ? LIMIT 1',
                $row->TABLE_NAME,
                $row->COLUMN_NAME,
                '%' . EditorJsImageFacade::DirName . '%',
            )->fetch();
            if ($exists !== null) {
                $columns[] = ['table' => $row->TABLE_NAME, 'column' => $row->COLUMN_NAME];
            }
        }
        return $columns;
    }

    /**
     * @param list<array{table: string, column: string}> $columns
     */
    private function replaceReferences(array $columns, string $oldName, string $newName): int
    {
        $old = $this->editorJsImageFacade->getUrl($oldName);
        $new = $this->editorJsImageFacade->getUrl($newName);
        $affected = 0;
        foreach ($columns as $column) {
            // JSON může mít lomítka escapovaná jako \/
            $affected += $this->explorer->query(
                'UPDATE ?name SET ?name = REPLACE(REPLACE(?name, ?, ?), ?, ?) WHERE ?name LIKE ?',
                $column['table'],
                $column['column'],
                $column['column'],
                $old,
                $new,
                str_replace('/', '\/', $old),
                str_replace('/', '\/', $new),
                $column['column'],
                '%' . $oldName . '%',
            )->getRowCount() ?? 0;
        }
        return $affected;
    }

    private function formatBytes(int $bytes): string
    {
        return $bytes < 1024 * 1024
            ? round($bytes / 1024) . ' kB'
            : round($bytes / 1024 / 1024, 1) . ' MB';
    }
}
