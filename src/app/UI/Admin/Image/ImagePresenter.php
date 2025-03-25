<?php

namespace App\UI\Admin\Image;

use App\Component\Datagrid\DataGrid;
use App\Component\Datagrid\DataGridFactory;
use App\Component\Image\Form\FormFactory;
use App\Model\Admin\Setting;
use App\Model\Entity\ImageEntity;
use App\UI\Accessory\Admin\Form\Form;
use App\UI\Accessory\Admin\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use App\UI\Accessory\Admin\Submenu\SubmenuFactory;
use App\UI\Accessory\ParameterBag;
use App\UI\Admin\Image\DataGrid\DefaultDataGridEntityFactory;
use JetBrains\PhpStorm\NoReturn;
use Nette\Application\UI\Presenter;
use Nette\Http\FileUpload;
use Nette\Utils\Arrays;
use Nette\Utils\FileSystem;
use Nette\Utils\Image;
use Nette\Utils\Random;

class ImagePresenter extends Presenter
{
    use RequireLoggedUserTrait;
    use StandardTemplateTrait;

    /**
     * @var ImageEntity
     */
    private \Nette\Database\Table\ActiveRow $image;

    public function __construct(
        private readonly ParameterBag                 $parameterBag,
        private readonly DataGridFactory              $dataGridFactory,
        private readonly DefaultDataGridEntityFactory $defaultDataGridEntityFactory,
        private readonly FormFactory                  $formFactory,
        private readonly Setting                      $settingModel,
        private readonly SubmenuFactory               $submenuFactory,
    ) {
        parent::__construct();
    }

    #[NoReturn] public function actionDeleteUnused():void
    {
        $this->imageFacade->deleteUnused();
        $this->flashMessage($this->translator->translate('flash_deleteUnusedImages'));
        $this->redirect('default');
    }

    public function actionDefault():void
    {
        $this->submenuFactory->addMenu($this->translator->translate('menu_deleteUnused'), 'deleteUnused')
            ->setIsPrimary()
        ;
    }

    protected function createComponentFormEdit():Form
    {
        $form = $this->createComponentEditImageForm();
        $form->setDefaults([
            'alt' => $this->image->alt,
            'name' => $this->image->name,
            'description' => $this->image->description,
            'author' => $this->image->author,
            'image_id' => $this->image->id,
        ]);
        return $form;
    }

    private function exist(int $id): void
    {
        $image = $this->imageModel->get($id);
        if (null === $image) {
            $this->error($this->translator->translate('flash_imageNotFound'));
        }
        $this->image = $image;
    }

    public function actionEdit(int $id):void
    {
        $this->exist($id);
        $this->template->imageEntity = $this->image;
    }

    protected function createComponentGrid():DataGrid{
        return $this->dataGridFactory->create($this->imageModel->getToGrid(), $this->defaultDataGridEntityFactory->create());
    }

    #[NoReturn] public function actionUpload(int $locationId): void
    {
        $file = $this->getHttpRequest()->getFile('file');
        if ($file instanceof FileUpload) {
            if ($file->isOk()) {
                $suffix = Arrays::last(explode('.', $file->getSanitizedName()));

                $fileName = md5(time() . '_' . Random::generate()) . '.' . $suffix;

                $image = $this->imageModel->insert([
                    'original_name' => $file->getUntrustedName(),
                    'saved_name' => $fileName,
                    'image_location_id' => $locationId,
                ]);

                FileSystem::createDir($this->parameterBag->uploadDir);
                if($file->isImage()) {
                    $netteImage = $file->toImage();
                    $setting = $this->settingModel->getDefault();
                    if($setting?->max_image_resolution !== null){
                        $netteImage->resize($setting->max_image_resolution, $setting->max_image_resolution, Image::ShrinkOnly);
                    }
                    $netteImage->save($this->parameterBag->uploadDir . '/' . $fileName);
                }else{
                    $file->move($this->parameterBag->uploadDir . '/' . $fileName);
                }
                $this->payload->file = $fileName;
                $this->payload->imageId = $image->id;
            } else {
                $this->payload->error = $this->translator->translate('flash_fileSaveFailed');
            }
        } else {
            $this->payload->error = $this->translator->translate('flash_internalError');
        }
        $this->sendPayload();
    }
}
