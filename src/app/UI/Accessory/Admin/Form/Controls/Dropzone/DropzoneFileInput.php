<?php

namespace App\UI\Accessory\Admin\Form\Controls\Dropzone;

use App\Model\Admin\File;
use App\UI\Accessory\Admin\Form\Form;
use Nette\Application\LinkGenerator;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Html;

class DropzoneFileInput extends TextInput
{
    public bool $multiple = false {
        get {
            return $this->multiple;
        }
        set {
            $this->multiple = $value;
        }
    }

    public function __construct(
        private readonly LinkGenerator $linkGenerator,
        private readonly File                           $fileModel,
        null|string|\Stringable                          $label = null,
        ?int                                             $maxLength = null
    ) {
        parent::__construct($label, $maxLength);
    }

    /**
     * @return null|int|int[]
     */
    public function getValue(): null|int|array
    {
        if(parent::getValue() === '' || parent::getValue() === null){
            return null;
        }
        if($this->multiple){
            return explode(';', parent::getValue());
        }else{
            return (int)parent::getValue();
        }
    }

    public function getControlDropzone(?string $class, TextInput $input): Html
    {
        $link = Html::el();
        if ($input->getValue()) {
            $fileRow = $this->fileModel->get($input->getValue());
            if($fileRow !== null) {
                $link = Html::el('a')->href($this->linkGenerator->link('Admin:File:download', ['id' => $fileRow['id']]))->setText($fileRow->original_name);
            }
        }

        $langChange = $input->getControl()->getAttribute(Form::LANG_CHANGE_ATTRIBUTE);

        $container = Html::el('div')->class($class . ' dropzoneContainer');
        if($langChange !== null){
            $container->setAttribute(Form::LANG_CHANGE_ATTRIBUTE, true);
            $container->setAttribute('data-language-id', $input->getControl()->getAttribute('data-language-id'));
        }
        $dropzone = Html::el('div')
            ->setClass('dropzone dropzoneFile')
            ->setAttribute('data-upload-url', $this->linkGenerator->link('Admin:File:upload'))
            ->addHtml(Html::el('div')->class('ms-4')->addHtml($link))
        ;
        $dropzone->setAttribute('data-multiple', $this->multiple);
        $dropzone->setAttribute('data-chunksize', $this->convertToBytes(ini_get('upload_max_filesize')));

        $container->addHtml($input->getControl()->style('display: none'));
        $container->addHtml($dropzone);

        return $container;
    }

    function convertToBytes(string $value):float {
        $value = trim($value);
        $lastChar = strtolower($value[strlen($value)-1]);
        $number = (int)$value;

        switch($lastChar) {
            case 'g':
                $number *= 1024;
            case 'm':
                $number *= 1024;
            case 'k':
                $number *= 1024;
        }
        return $number;
    }
}