<?php

namespace App\UI\Admin\EditorJs;

use App\Component\EditorJs\EditorJsImageFacade;
use App\UI\Accessory\Admin\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use JetBrains\PhpStorm\NoReturn;
use Nette\Application\UI\Presenter;
use Nette\Http\Request;

class EditorJsPresenter extends Presenter
{
    use RequireLoggedUserTrait;
    use StandardTemplateTrait;

    public function __construct(
        private readonly Request             $request,
        private readonly EditorJsImageFacade $editorJsImageFacade,
    )
    {
        parent::__construct();
    }

    #[NoReturn]
    public function actionUpload():void
    {
        $file = ($this->request->getFile('image'));
        if($file === null){
            $file = $this->request->getFile('file');
        }
        if ($file !== null && $file->isOk()) {
            $this->sendJson([
                'success' => 1,
                'file' => [
                    'url' => $this->editorJsImageFacade->saveUpload($file),
                ]
            ]);
        }else{
            $this->sendJson([
                'success' => 0,
            ]);
        }
    }
}
