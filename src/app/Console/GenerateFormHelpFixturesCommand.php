<?php

namespace App\Console;

use App\Model\Admin\FormHelp;
use App\Model\Admin\FormHelpLanguage;
use App\Model\Admin\Language;
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

#[AsCommand(name: 'form:generateHelpFixtures', description: 'Generate form help fixtures')]
class GenerateFormHelpFixturesCommand extends Command
{
    public function __construct(
        private readonly Language         $languageModel,
        private readonly FormHelp         $formHelpModel,
        private readonly FormHelpLanguage $formHelpLanguageModel,
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
            ->addUse(\App\Model\DoctrineEntity\FormHelpLanguage::class)
            ->addUse(\App\Model\DoctrineEntity\FormHelp::class)
            ->addUse(\App\Model\DoctrineEntity\Language::class)
        ;

        $class = $namespace->addClass('FormHelpFixtures');
        $class
            ->addImplement(FixtureInterface::class)
            ->addImplement(DependentFixtureInterface::class)
            ->setExtends(Fixture::class)
        ;

        $propertyFormHelps = $class->addProperty('formHelpsData')->setPrivate()->setType('array');
        $values = [];
        foreach($this->formHelpModel->getAll() as $formHelp){
            $data = $formHelp->toArray();
            $data['languages'] = [];
            foreach($this->formHelpLanguageModel->getByFormHelp($formHelp) as $formHelpLanguage){
                $formHelpLanguageData = $formHelpLanguage->toArray();
                unset($formHelpLanguageData['id']);
                $data['languages'][] = $formHelpLanguageData;
            }
            $values[] = $data;
        }
        $propertyFormHelps->setValue($values);

        $class->addProperty('languages')->setPrivate()->setType('array');

        $method = $class->addMethod('load')
            ->setPublic()
            ->setReturnType('void')
        ;
        $method->addParameter('manager')->setType(ObjectManager::class);
        $method->setBody('

foreach($manager->getRepository(Language::class)->findAll() as $language){
    $this->languages[$language->id] = $language;
}

foreach($this->formHelpsData as $formHelpData) {
    $formHelp = $manager->getRepository(FormHelp::class)->findOneBy([
        \'presenter\' => $formHelpData[\'presenter\'],
        \'input\' => $formHelpData[\'input\'],
    ]);
    $languages = $formHelpData[\'languages\'];
    unset($formHelpData[\'languages\']);
    if($formHelp === null){
        $formHelp = new FormHelp();
    }
    $formHelp->presenter = $formHelpData[\'presenter\'];
    $formHelp->input = $formHelpData[\'input\'];
    $formHelp->label_help = $formHelpData[\'label_help\'];
    $manager->persist($formHelp);
    
    foreach($languages as $formHelpLanguageData){
        $formHelpLanguage = $manager->getRepository(FormHelpLanguage::class)->findOneBy([
            \'form_help\' => $formHelp,
            \'language\' => $this->languages[$formHelpLanguageData[\'language_id\']],
        ]);
        if($formHelpLanguage === null){
            $formHelpLanguage = new FormHelpLanguage();
            $formHelpLanguage->form_help = $formHelp;
            $formHelpLanguage->language = $this->languages[$formHelpLanguageData[\'language_id\']];
        }
        $formHelpLanguage->label_help = $formHelpLanguageData[\'label_help\'];
        $manager->persist($formHelpLanguage);
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
        FileSystem::write($dir . '/FormHelpFixtures.php', (string)$file);

        $output->writeln('<info>Fixtures generated</info>');

        return self::SUCCESS;
    }
}