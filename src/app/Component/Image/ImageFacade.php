<?php

namespace App\Component\Image;

use App\Component\Image\Exception\ImageNotFoundException;
use App\Component\Image\Form\EditFormData;
use App\Component\Log\LogActionEnum;
use App\Component\Log\LogFacade;
use App\Model\Entity\ImageEntity;
use App\UI\Accessory\ParameterBag;
use Doctrine\ORM\Mapping\Table;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\DI\Container;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Nette\Utils\Html;
use Nette\Utils\Image;
use Nette\Utils\ImageColor;

readonly class ImageFacade
{
    public function __construct(
        private ParameterBag           $parameterBag,
        private \App\Model\Admin\Image $imageModel,
        private Container              $container,
        private Explorer               $explorer,
        private LogFacade $logFacade,
    ) {}

    /**
     * @return array<int, ImageEntity>
     */
    public function getUsedImages():array
    {
        $images = [];

        $reflection = new \ReflectionClass($this->container);
        $property = $reflection->getProperty('wiring');
        $property->setAccessible(true);
        $services = $property->getValue($this->container);
        foreach (array_keys($services) as $class) {
            $reflection = new \ReflectionClass($class);
            if ($reflection->getAttributes(Table::class)) {
                foreach ($reflection->getProperties() as $property) {
                    if (str_contains($property->getType()->getName(), 'DoctrineEntity')) {
                        if($property->getType()->getName() === \App\Model\DoctrineEntity\Image::class){
                            foreach($reflection->getAttributes() as $attribute){
                                if($attribute->getName() === Table::class){
                                    $table = $attribute->getArguments()['name'];
                                    $model = $this->explorer->table($table)->where($property->getName() . '_id IS NOT NULL');
                                    foreach($model as $row){
                                        $images[$row[$property->getName() . '_id']] = $row->ref('image', $property->getName() . '_id');
                                    }
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $images;
    }

    /**
     * @param EditFormData $data
     * @return void
     * @throws ImageNotFoundException
     */
    public function edit(EditFormData $data):void{
        $image = $this->imageModel->get($data->image_id);
        if($image === null){
            throw new ImageNotFoundException();
        }
        $updateData = (array)$data;
        unset($updateData['image_id']);
        $image->update($updateData);
        $this->logFacade->create(LogActionEnum::Updated, 'image', $image->id);
    }

    /**
     * @param ImageDto $image
     * @param int|null $width
     * @param int|null $height
     * @return string
     */
    public function getPreviewName(ImageDto $image, ?int $width = null, ?int $height = null, int $type = \Nette\Utils\Image::ShrinkOnly): string
    {
        if($width === null && $height === null){
            return 'null_null_' . $image->saved_name;
        }else {
            $name = explode('.', $image->saved_name);
            unset($name[count($name) - 1]);
            $name = implode('.', $name) . '.webp';
            return (null === $width ? 'null' : $width) . '_' . (null === $height ? 'null' : $height) . '_' . (string)$type . '_' .$name;
        }
    }

    /**
     * @param ImageDto $image
     * @param int|null $width
     * @param int|null $height
     * @return ?Image
     * @throws \Nette\Utils\ImageException
     * @throws \Nette\Utils\UnknownImageFileException
     * @throws \Exception
     */
    public function generatePreview(ImageDto $image, ?int $width = null, ?int $height = null, int $type = \Nette\Utils\Image::ShrinkOnly): ?Image
    {
        if (!file_exists($this->parameterBag->uploadDir.'/'.$image->saved_name)) {
            return null;
        }
        $imageNette = Image::fromFile($this->parameterBag->uploadDir.'/'.$image->saved_name);
        if (null === $width && null === $height) {
            $imageNette->save($this->parameterBag->previewDir.'/'.$this->getPreviewName($image, $width, $height, $type));

            return $imageNette;
        }
        $imageNette->resize($width, $height, $type);
        $imageNette->sharpen();
        $imageNette->alphaBlending(false);
        $imageNette->saveAlpha(true);
        $container = Image::fromBlank($width, $height, ImageColor::rgb(255, 255, 255, 0));
        $container->alphaBlending(false);
        $container->saveAlpha(true);
        $container->place($imageNette, '50%', '50%');
        FileSystem::createDir($this->parameterBag->previewDir);
        $container->save($this->parameterBag->previewDir.'/'.$this->getPreviewName($image, $width, $height, $type), 100);

        return $container;
    }

    public function deleteUnused():void
    {
        $used = $this->getUsedImages();
        foreach($this->imageModel->getTable() as $image){
            if(!array_key_exists($image->id, $used)){
                $image->delete();
            }
        }

        $usedHashes = [];
        foreach($used as $image){
            $usedHashes[] = $image->saved_name;
        }

        foreach(Finder::findFiles('*.*')->in($this->parameterBag->uploadDir) as $file){
            if(!in_array($file->getFilename(), $usedHashes)){
                FileSystem::delete($file);
            }
        }

        $this->logFacade->create(LogActionEnum::DeletedUnused, 'image');
    }
}
