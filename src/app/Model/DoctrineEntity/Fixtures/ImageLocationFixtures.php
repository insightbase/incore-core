<?php

namespace App\Model\DoctrineEntity\Fixtures;

use App\Model\DoctrineEntity\ImageLocation;
use App\UI\Accessory\Admin\Form\Controls\Dropzone\DropzoneImageLocationEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ImageLocationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $favicon = $manager->getRepository(ImageLocation::class)->findOneBy(['location' => DropzoneImageLocationEnum::Favicon->value]);
        if(!$favicon){
            $favicon = new ImageLocation();
            $favicon->location = DropzoneImageLocationEnum::Favicon->value;
            $manager->persist($favicon);
            $manager->flush();
        }

        $settingShareImage = $manager->getRepository(ImageLocation::class)->findOneBy(['location' => DropzoneImageLocationEnum::SettingShareImage->value]);
        if(!$settingShareImage){
            $settingShareImage = new ImageLocation();
            $settingShareImage->location = DropzoneImageLocationEnum::SettingShareImage->value;
            $manager->persist($settingShareImage);
            $manager->flush();
        }

        $settingLogo = $manager->getRepository(ImageLocation::class)->findOneBy(['location' => DropzoneImageLocationEnum::SettingLogo->value]);
        if(!$settingLogo){
            $settingLogo = new ImageLocation();
            $settingLogo->location = DropzoneImageLocationEnum::SettingLogo->value;
            $manager->persist($settingLogo);
            $manager->flush();
        }

        $languageFlag = $manager->getRepository(ImageLocation::class)->findOneBy(['location' => DropzoneImageLocationEnum::LanguageFlag->value]);
        if(!$languageFlag){
            $languageFlag = new ImageLocation();
            $languageFlag->location = DropzoneImageLocationEnum::LanguageFlag->value;
            $manager->persist($languageFlag);
            $manager->flush();
        }

        $userAvatar = $manager->getRepository(ImageLocation::class)->findOneBy(['location' => DropzoneImageLocationEnum::UserAvatar->value]);
        if(!$userAvatar){
            $userAvatar = new ImageLocation();
            $userAvatar->location = DropzoneImageLocationEnum::UserAvatar->value;
            $manager->persist($userAvatar);
            $manager->flush();
        }

        $content = $manager->getRepository(ImageLocation::class)->findOneBy(['location' => DropzoneImageLocationEnum::Content->value]);
        if(!$content){
            $content = new ImageLocation();
            $content->location = DropzoneImageLocationEnum::Content->value;
            $manager->persist($content);
            $manager->flush();
        }

        $blog = $manager->getRepository(ImageLocation::class)->findOneBy(['location' => DropzoneImageLocationEnum::Blog->value]);
        if(!$blog){
            $blog = new ImageLocation();
            $blog->location = DropzoneImageLocationEnum::Blog->value;
            $manager->persist($blog);
            $manager->flush();
        }
    }
}