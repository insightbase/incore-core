<?php

namespace App\Console;

use App\Model\Admin\Language;
use App\Model\Admin\Translate;
use App\Model\Admin\TranslateLanguage;
use App\Model\DoctrineEntity\Fixtures\LanguageFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Nette\PhpGenerator\PhpFile;
use Nette\Utils\FileSystem;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'translate:generateFixtures', description: 'Generate translate fixtures')]
class GenerateTranslatesFixtureCommand extends Command
{
    public function __construct(
        private readonly Translate         $translateModel,
        private readonly Language          $languageModel,
        private readonly TranslateLanguage $translateLanguage,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = dirname(__FILE__) . '/../Model/DoctrineEntity/Fixtures';
        $file = new PhpFile();
        $file->setStrictTypes();

        $namespace = $file->addNamespace('App\Model\DoctrineEntity\Fixtures')
            ->addUse(\App\Model\DoctrineEntity\Translate::class)
            ->addUse(\App\Model\DoctrineEntity\TranslateLanguage::class)
            ->addUse(LanguageFixtures::class)
            ->addUse(\App\Model\DoctrineEntity\Language::class)
        ;
        $class = $namespace->addClass('TranslateFixtures');
        $class
            ->addImplement(FixtureInterface::class)
            ->addImplement(DependentFixtureInterface::class)
            ->setExtends(Fixture::class)
        ;
        $propertyTranslates = $class->addProperty('translates')->setPrivate()->setType('array');

        $language = $this->languageModel->getByUrl('cs');
        $translates = [];
        foreach($this->translateModel->getAll() as $translate){
            $translateLanguage = $this->translateLanguage->getByTranslateAndLanguage($translate, $language);
            $translates[$translate->key] = $translateLanguage?->value;
        }
        $propertyTranslates->setValue($translates);

        $method = $class->addMethod('load')
            ->setPublic()
            ->setReturnType('void')
        ;
        $method->addParameter('manager')->setType(ObjectManager::class);
        $method->setBody('
        $language = $this->getReference(LanguageFixtures::LANG_CS, Language::class);
        foreach($this->translates as $key => $value){
            $exist = $manager->getRepository(Translate::class)->findOneBy([\'key\' => $key]);
            if($exist === null){
                $translate = new Translate();
                $translate->setKey($key);
                $manager->persist($translate);
        
                if($value !== null){
                    $translateLanguage = new TranslateLanguage();
                    $translateLanguage->setLanguage($language);
                    $translateLanguage->setTranslate($translate);
                    $translateLanguage->setValue($value);
                    $manager->persist($translateLanguage);
                }
            }
        }
        $manager->flush();
');

        $methodDepend = $class->addMethod('getDependencies')
            ->setPublic()
            ->setReturnType('array')
            ->setBody('
return [
    LanguageFixtures::class,
];
');

        FileSystem::write($dir . '/TranslateFixtures.php', (string)$file);

        $output->writeln('<info>Fixtures generated</info>');

        return self::SUCCESS;
    }
}