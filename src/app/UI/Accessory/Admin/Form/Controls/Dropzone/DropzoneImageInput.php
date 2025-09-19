<?php

namespace App\UI\Accessory\Admin\Form\Controls\Dropzone;

use App\Component\Image\ImageControlFactory;
use App\Model\Admin\Image;
use App\Model\Admin\ImageLocation;
use App\UI\Accessory\Admin\Form\Form;
use Nette;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Html;

class DropzoneImageInput extends TextInput
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
        private readonly Nette\Application\LinkGenerator $linkGenerator,
        private readonly ImageControlFactory             $imageControlFactory,
        private readonly Image                           $imageModel,
        private readonly DropzoneImageLocationEnum       $locationEnum,
        private readonly ImageLocation                   $imageLocationModel,
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

    public function setDefaultValue($value)
    {
        if($this->multiple){
            $value = implode(';', $value);
        }
        return parent::setDefaultValue($value);
    }

    public function getControlDropzone(?string $class, TextInput $input): Html
    {
        $image = Html::el();
        if ($input->getValue()) {
            $imageControl = $this->imageControlFactory->create()->setParent($this->getForm()->getPresenter());
            if(!$this->multiple){
                $imageRow = $this->imageModel->get($input->getValue());
                if ($imageRow !== null) {
                    $image = Html::el('div')->class('dz-preview dz-file-preview')
                        ->addHtml(Html::el('div')->class('dz-image')->addHtml($imageControl->renderToString($input->getValue(), 100, 100)));
                }
            }
        }

        $langChange = $input->getControl()->getAttribute(Form::LANG_CHANGE_ATTRIBUTE);

        $container = Html::el('div')->class($class . ' dropzoneContainer');
        if($langChange !== null){
            $container->setAttribute(Form::LANG_CHANGE_ATTRIBUTE, true);
            $container->setAttribute('data-language-id', $input->getControl()->getAttribute('data-language-id'));
        }
        $imageLocation = $this->imageLocationModel->getByLocation($this->locationEnum);
        $dropzone = Html::el('div')
            ->setClass('dropzone dropzoneImage')
            ->setAttribute('data-upload-url', $this->linkGenerator->link('Admin:Image:upload', ['locationId' => $imageLocation->id]))
            ->addHtml(Html::el('div')->class('ms-4')->addHtml($image))
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
