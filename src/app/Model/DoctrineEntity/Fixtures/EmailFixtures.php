<?php

namespace App\Model\DoctrineEntity\Fixtures;

use App\Component\Mail\SystemNameEnum;
use App\Model\DoctrineEntity\Email;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class EmailFixtures implements FixtureInterface
{

    public function load(ObjectManager $manager): void
    {
        $exist = $manager->getRepository(Email::class)->findOneBy(['system_name' => SystemNameEnum::ForgotPassword->value]);
        if(!$exist){
            $email = (new Email())
                ->setSystemName(SystemNameEnum::ForgotPassword->value)
                ->setName('Zapomenuté heslo')
                ->setSubject('Obnovení zapomenutého hesla')
                ->setText('Pro obnovení klikněte na tento <a href="%link%">odkaz</a>')
                ->setModifier('link')
            ;
            $manager->persist($email);
            $manager->flush();
        }

        $exist = $manager->getRepository(Email::class)->findOneBy(['system_name' => SystemNameEnum::TestEmail->value]);
        if(!$exist){
            $email = (new Email())
                ->setSystemName(SystemNameEnum::TestEmail->value)
                ->setName('Testovací email')
                ->setSubject('Testovací email')
                ->setText('%message%')
                ->setModifier('message')
            ;
            $manager->persist($email);
            $manager->flush();
        }
    }
}