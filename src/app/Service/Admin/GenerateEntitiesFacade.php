<?php

namespace App\Service\Admin;

use App\UI\Accessory\ParameterBag;
use Doctrine\ORM\Mapping\Table;
use Nette\Database\Table\ActiveRow;
use Nette\DI\Container;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\Utils\DateTime;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Symfony\Component\Console\Output\OutputInterface;

readonly class GenerateEntitiesFacade
{
    public function __construct(
        private Container    $container,
        private ParameterBag $parameterBag,
    )
    {
    }

    public function generate(OutputInterface $output): void
    {
        $output->writeln('<info>Delete entities</info>');

        $ignoreDirs = ['.idea', 'build'];

        $modules = $this->parameterBag->appDir . '/../../';
        foreach (Finder::findDirectories('*')->in($modules)->exclude($ignoreDirs) as $incoreModule) {
            if (is_dir($incoreModule->getPathname() . '/src/app/Model/DoctrineEntity')) {
                FileSystem::delete($incoreModule->getPathname() . '/src/app/Model/Entity');
                FileSystem::createDir($incoreModule->getPathname() . '/src/app/Model/Entity');
            }
        }

        $output->writeln('<info>Generate entities</info>');
        $reflection = new \ReflectionClass($this->container);
        $property = $reflection->getProperty('wiring');
        $property->setAccessible(true);
        $services = $property->getValue($this->container);
        foreach (array_keys($services) as $class) {
            $reflection = new \ReflectionClass($class);
            if ($reflection->getAttributes(Table::class)) {
//                $output->writeln($reflection->getShortName());
                $module = '';
                foreach (explode('/', $reflection->getFileName()) as $dir) {
                    if ('src' === $dir) {
                        break;
                    }
                    $module = $dir;
                }

                $file = new PhpFile();
                $file->setStrictTypes();

                $namespace = $file->addNamespace('App\Model\Entity');
                $namespace->addUse(ActiveRow::class);
                $class = $namespace->addClass($className = $reflection->getShortName() . 'Entity');
                $class->setExtends(ActiveRow::class);

                foreach ($reflection->getProperties() as $property) {
                    $class->addComment(sprintf('@property-read %s $%s', ($property->getType()->allowsNull() ? '?' : '') . $this->getType($property->getType()->getName(), $namespace), $this->getPropertyName($property)));

                    if (str_contains($property->getType()->getName(), 'DoctrineEntity')) {
                        $class->addComment(sprintf('@property-read %s $%s', ($property->getType()->allowsNull() ? '?' : '') . 'int', $this->getPropertyName($property) . '_id'));
                    }
                }

                FileSystem::write($this->parameterBag->appDir . '/../../' . $module . '/src/app/Model/Entity/' . $className . '.php', $file);
            }
        }
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

    private function getPropertyName(\ReflectionProperty $property): string
    {
//        if (str_contains($property->getType()->getName(), 'DoctrineEntity')) {
//            $text = Strings::capitalize(str_replace('_', ' ', $property->getName()));
//
//            return Strings::firstLower(str_replace(' ', '', $text));
//        }

        return $property->getName();
    }
}