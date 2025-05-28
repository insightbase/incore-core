<?php

namespace App\UI\Admin\Favicon;

use App\Component\Log\LogActionEnum;
use App\Component\Log\LogFacade;
use App\Component\Translator\Translator;
use App\Model\Admin\Favicon;
use App\Model\Admin\Image;
use App\Model\Admin\ImageLocation;
use App\Model\Admin\Setting;
use App\Model\Entity\FaviconEntity;
use App\UI\Accessory\Admin\Form\Controls\Dropzone\DropzoneImageLocationEnum;
use App\UI\Accessory\ParameterBag;
use App\UI\Admin\Favicon\Exception\NotFoundFilesException;
use App\UI\Admin\Favicon\Form\FormNewData;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Arrays;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Nette\Utils\Random;

readonly class FaviconFacade
{
    public function __construct(
        private Favicon $faviconModel,
        private Image $imageModel,
        private Translator $translator,
        private Storage $storage,
        private LogFacade $logFacade,
        private ParameterBag $parameterBag,
        private Setting $settingModel,
        private ImageLocation $imageLocation,
    )
    {
    }

    private function cleanCache():void{
        new Cache($this->storage, 'favicon')->remove('default');
    }

    public function create(FormNewData $data):void
    {
        $favicon = $this->faviconModel->insert((array)$data);
        $this->logFacade->create(LogActionEnum::Created, 'favicon', $favicon->id);
        $this->cleanCache();
    }

    /**
     * @param FaviconEntity $favicon
     * @param Form\FormEditData $data
     * @return void
     */
    public function update(ActiveRow $favicon, Form\FormEditData $data):void
    {
        $favicon->update((array)$data);
        $this->logFacade->create(LogActionEnum::Updated, 'favicon', $favicon->id);
        $this->cleanCache();
    }

    /**
     * @param Form\FormImportData $data
     * @return void
     * @throws NotFoundFilesException
     */
    public function import(Form\FormImportData $data):void
    {
        $notFoundFiles = [];

        $xml = new \DOMDocument();
        $xml->loadHTML($data->html);

        $images = [];
        foreach($this->imageModel->getByIds($data->files) as $image){
            if(strtolower(pathinfo($this->parameterBag->uploadDir . '/' . $image->saved_name, PATHINFO_EXTENSION)) === 'zip'){
                $dir = $this->parameterBag->tempDir . '/favion-unzipped/';
                FileSystem::delete($dir);
                FileSystem::createDir($dir);
                $zip = new \ZipArchive();
                if($zip->open($this->parameterBag->uploadDir . '/' . $image->saved_name) === true){
                    $zip->extractTo($dir);
                    $zip->close();
                    foreach(Finder::findFiles('*')->in($dir) as $name => $file){
                        $suffix = Arrays::last(explode('.', $name));
                        $fileName = md5(time() . '_' . Random::generate()) . '.' . $suffix;
                        $image = $this->imageModel->insert([
                            'original_name' => $file->getBasename(),
                            'saved_name' => $fileName,
                            'image_location_id' => $this->imageLocation->getByLocation(DropzoneImageLocationEnum::Favicon)->id,
                        ]);
                        FileSystem::createDir($this->parameterBag->uploadDir);
                        FileSystem::copy($name, $this->parameterBag->uploadDir . '/' . $fileName);
                        $images[$file->getBasename()] = $image->id;
                    }
                }
            }else {
                $images[$image->original_name] = $image->id;
            }
        }

        $this->faviconModel->truncate();

        /** @var \DOMElement $element */
        foreach($xml->getElementsByTagName('*') as $element){
            if(in_array($element->nodeName, ['link', 'meta'])) {
                $data = [
                    'rel' => $element->getAttribute('rel') === '' ? null : $element->getAttribute('rel'),
                    'type' => $element->getAttribute('type') === '' ? null : $element->getAttribute('type'),
                    'sizes' => $element->getAttribute('sizes') === '' ? null : $element->getAttribute('sizes'),
                    'name' => $element->getAttribute('name') === '' ? null : $element->getAttribute('name'),
                    'tag' => $element->nodeName,
                ];
                if($element->getAttribute('content') !== '' && $element->getAttribute('content') !== null){
                    $url = (parse_url($element->getAttribute('content'), PHP_URL_PATH));
                    if($url === null){
                        $notFoundFiles[] = $element->getAttribute('content');
                        $data['content'] = $element->getAttribute('content');
                    }else {
                        $fileInfo = pathinfo(parse_url($element->getAttribute('content'), PHP_URL_PATH));
                        $fileName = $fileInfo['basename'];
                        if (array_key_exists($fileName, $images)) {
                            $data['image_id'] = $images[$fileName];
                            $data['image_to_attribute'] = 'content';
                        } else {
                            $notFoundFiles[] = $fileName;
                            $data['content'] = $element->getAttribute('content');
                        }
                    }
                }
                if($element->getAttribute('href') !== '' && $element->getAttribute('href') !== null){
                    $url = (parse_url($element->getAttribute('href'), PHP_URL_PATH));
                    if($url === null){
                        $notFoundFiles[] = $element->getAttribute('href');
                        $data['href'] = $element->getAttribute('href');
                    }else {
                        $fileInfo = pathinfo(parse_url($element->getAttribute('href'), PHP_URL_PATH));
                        $fileName = $fileInfo['basename'];
                        if (array_key_exists($fileName, $images)) {
                            $data['image_id'] = $images[$fileName];
                            $data['image_to_attribute'] = 'href';
                        } else {
                            $notFoundFiles[] = $fileName;
                            $data['href'] = $element->getAttribute('href');
                        }
                    }
                }
                $this->faviconModel->insert($data);
            }
        }
        $this->cleanCache();
        $this->logFacade->create(LogActionEnum::Imported, 'favicon');

        if(!empty($notFoundFiles)){
            throw new NotFoundFilesException($this->translator->translate('flash_filesNotFound%files%', ['files' => implode(', ', $notFoundFiles)]));
        }
    }

    /**
     * @param FaviconEntity $favicon
     * @return void
     */
    public function delete(ActiveRow $favicon):void
    {
        $favicon->delete();
    }
}