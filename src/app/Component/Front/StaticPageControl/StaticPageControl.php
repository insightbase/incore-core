<?php

namespace App\Component\Front\StaticPageControl;

use App\Component\EditorJs\EditorJsRendererFactory;
use App\Component\Front\StaticPageControl\Dto\StaticPageDto;
use App\Component\Image\ImageControl;
use App\Component\Image\ImageControlFactory;
use App\Component\Translator\Translator;
use App\Model\Admin\StaticPage;
use App\Model\Admin\StaticPageLanguage;
use App\Model\Entity\LanguageEntity;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Control;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Database\Table\ActiveRow;

/**
 * @property-read Template $template
 */
class StaticPageControl extends Control
{
    public const string CACHE_NAMESPACE = 'static_page';

    public function __construct(
        private readonly Storage                 $storage,
        private readonly Translator              $translator,
        private readonly StaticPage              $staticPageModel,
        private readonly StaticPageLanguage      $staticPageLanguageModel,
        private readonly ImageControlFactory     $imageControlFactory,
        private readonly EditorJsRendererFactory $editorJsRendererFactory,
    )
    {
    }

    protected function createComponentImage():ImageControl
    {
        return $this->imageControlFactory->create();
    }

    /**
     * @param string $systemName
     * @param LanguageEntity $language
     * @return StaticPageDto|null
     * @throws \Throwable
     */
    public function getStaticPage(string $systemName, ActiveRow $language):?StaticPageDto
    {
        $cache = new Cache($this->storage, self::CACHE_NAMESPACE);
        $staticPageDto = $cache->load('detail_' . $this->translator->getLanguage()->id . '_' . $systemName, function () use ($systemName, $language) {
            $staticPage = $this->staticPageModel->getBySystemName($systemName);
            if($staticPage === null || !$staticPage->active){
                throw new BadRequestException();
            }

            $dto = new StaticPageDto($staticPage->id, $staticPage->name, $staticPage->title, $staticPage->description, $staticPage->keywords, $staticPage->content);

            if(!$language->is_default){
                $staticPageLanguage = $this->staticPageLanguageModel->getByStaticPageAndLanguage($staticPage, $language);

                $keys = ['name', 'title', 'description', 'keywords', 'content'];

                foreach($keys as $key) {
                    if ($staticPageLanguage?->$key !== null) {
                        $dto->$key = $staticPageLanguage->$key;
                    }
                }
            }

            return json_encode($dto);
        }, [Cache::Tags => ['static_page_' . $systemName]]);

        return StaticPageDto::fromJson($staticPageDto);
    }

    public function renderDetail(string $systemName, string $templateFile):void
    {
        $this->template->staticPageDto = $this->getStaticPage($systemName, $this->translator->getLanguage());
        $this->template->setFile($templateFile);
        $this->template->imageControl = $this->imageControlFactory->create();
        $this->template->editorJsRenderer = $this->editorJsRendererFactory->create();
        $this->template->render();
    }
}