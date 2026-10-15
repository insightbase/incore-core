<?php

declare(strict_types=1);

namespace App\UI\Admin\EditorJsThumbnail;

use App\Component\EditorJs\EditorJsImageFacade;
use Nette\Application\Responses\FileResponse;
use Nette\Application\UI\Presenter;

/**
 * Veřejný (bez přihlášení) - šablona sem odkazuje, dokud náhled (default)
 * nebo FullHD verze (large) obrázku z editor.js neexistuje. První zobrazení
 * ji vygeneruje, další vykreslení stránky už míří rovnou na statický soubor.
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
        $this->checkName($name);
        $this->sendGenerated(
            $name,
            fn() => $this->editorJsImageFacade->createThumbnail($name),
            $this->editorJsImageFacade->getThumbnailPath($name),
        );
    }

    public function actionLarge(string $name): void
    {
        $this->checkName($name);
        $this->sendGenerated(
            $name,
            fn() => $this->editorJsImageFacade->createLarge($name),
            $this->editorJsImageFacade->getLargePath($name),
        );
    }

    private function checkName(string $name): void
    {
        if (!$this->editorJsImageFacade->isLocalFile($name) || !$this->editorJsImageFacade->isOptimizable($this->editorJsImageFacade->getDir() . '/' . $name)) {
            $this->error();
        }
    }

    private function sendGenerated(string $name, \Closure $generate, string $path): void
    {
        try {
            $generate();
        } catch (\Throwable) {
            // poškozený obrázek - aspoň originál
            $this->redirectUrl($this->editorJsImageFacade->getUrl($name));
        }

        $this->sendResponse(new FileResponse(
            $path,
            contentType: 'image/webp',
            forceDownload: false,
        ));
    }
}
