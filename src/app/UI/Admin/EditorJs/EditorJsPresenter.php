<?php

namespace App\UI\Admin\EditorJs;

use App\UI\Accessory\Admin\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use App\UI\Accessory\ParameterBag;
use JetBrains\PhpStorm\NoReturn;
use Nette\Application\UI\Presenter;
use Nette\Http\Request;
use Nette\Utils\FileSystem;

class EditorJsPresenter extends Presenter
{
    use RequireLoggedUserTrait;
    use StandardTemplateTrait;

    public function __construct(
        private readonly Request      $request,
        private readonly ParameterBag $parameterBag,
    )
    {
        parent::__construct();
    }

    #[NoReturn]
    public function actionUpload():void
    {
        $dir = $this->parameterBag->wwwDir . '/editor-js';
        FileSystem::createDir($dir);

        $file = ($this->request->getFile('image'));
        if($file === null){
            $file = $this->request->getFile('file');
        }
        if ($file->isOk()) {
            $file->move($dir . '/' . $file->getSanitizedName());
            $this->sendJson([
                'success' => 1,
                'file' => [
                    'url' => '/editor-js/' . $file->getSanitizedName(),
                ]
            ]);
        }else{
            $this->sendJson([
                'success' => 0,
            ]);
        }
    }
}