<?php

namespace App\Component\File;

use App\Model\Admin\File;
use App\UI\Accessory\ParameterBag;
use Nette\Utils\FileSystem;

readonly class FileControl
{


    public function __construct(
        private ParameterBag $parameterBag,
        private File $fileModel,
    )
    {
    }

    public function getOriginal(int $fileId):string
    {
        $file = $this->fileModel->get($fileId);
        if($file !== null) {
            if (!file_exists($this->parameterBag->previewDir . '/' . $file->saved_name)) {
                if (file_exists($this->parameterBag->uploadDir . '/' . $file->saved_name)) {
                    FileSystem::copy($this->parameterBag->uploadDir . '/' . $file->saved_name, $this->parameterBag->previewDir . '/' . $file->saved_name);
                    return $this->parameterBag->previewWwwDir . '/' . $file->saved_name;
                }
            }else{
                return $this->parameterBag->previewWwwDir . '/' . $file->saved_name;
            }
        }
        return '';
    }
}