<?php

declare(strict_types=1);

namespace App\UI\Admin\EditorJsThumbnail;

use App\Component\EditorJs\EditorJsImageFacade;
use Nette\Application\Responses\FileResponse;
use Nette\Application\UI\Presenter;

/**
 * Veřejný (bez přihlášení) - šablona sem odkazuje, dokud náhled obrázku
 * z editor.js neexistuje. První zobrazení ho vygeneruje, další vykreslení
 * stránky už míří rovnou na statický soubor.
 */
class EditorJsThumbnailPresenter extends Presenter
{
    public function __construct(
        private readonly EditorJsImageFacade $editorJsImageFacade,
    ) {
        parent::__construct();
    }

    public function actionDefault(string $name): void
    {
        if (!$this->editorJsImageFacade->isLocalFile($name) || !$this->editorJsImageFacade->isOptimizable($this->editorJsImageFacade->getDir() . '/' . $name)) {
            $this->error();
        }

        try {
            $this->editorJsImageFacade->createThumbnail($name);
        } catch (\Throwable) {
            // poškozený obrázek - aspoň originál
            $this->redirectUrl($this->editorJsImageFacade->getUrl($name));
        }

        $this->sendResponse(new FileResponse(
            $this->editorJsImageFacade->getThumbnailPath($name),
            contentType: 'image/webp',
            forceDownload: false,
        ));
    }
}
