<?php

namespace App\Component\Front\FaviconControl;

use App\Component\Image\ImageControlFactory;
use App\Model\Admin\Image;
use App\Model\Front\Favicon;
use App\UI\Accessory\ParameterBag;
use Nette\Application\UI\Control;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Utils\Arrays;
use Nette\Utils\FileSystem;
use Nette\Utils\Html;
use Nette\Utils\Strings;

/**
 * @property-read Template $template
 */
class FaviconControl extends Control
{
    public function __construct(
        private readonly Favicon             $faviconModel,
        private readonly Storage             $storage,
        private readonly ImageControlFactory $imageControlFactory,
        private readonly ParameterBag        $parameterBag,
        private readonly Image               $imageModel,
    )
    {
    }

    public function getIcon(FaviconDto $faviconDto):Html{
        $tag = Html::el($faviconDto->tag);

        if($faviconDto->rel !== null){
            $tag->setAttribute('rel', $faviconDto->rel);
        }
        if($faviconDto->type !== null){
            $tag->setAttribute('type', $faviconDto->type);
        }
        if($faviconDto->sizes !== null){
            $tag->setAttribute('sizes', $faviconDto->sizes);
        }
        if($faviconDto->name !== null){
            $tag->setAttribute('name', $faviconDto->name);
        }
        if($faviconDto->name !== null){
            $tag->setAttribute('name', $faviconDto->name);
        }
        if($faviconDto->content !== null){
            $tag->setAttribute('content', $faviconDto->content);
        }
        if($faviconDto->imageId !== null){
            $tag->setAttribute($faviconDto->image_to_attribute, $this->imageControlFactory->create()->getOriginal($faviconDto->imageId, true));
        }

        return $tag;
    }

    /**
     * @return array<FaviconDto>
     * @throws \Throwable
     */
    protected function getFavicons(): array
    {
        $cache = new Cache($this->storage, 'favicon');
        return $cache->load('default', function(){
            $favicons = [];
            foreach($this->faviconModel->getToFront() as $favicon){

                if($favicon->image_id !== null && str_ends_with($favicon->image->original_name, '.json')){
                    if(file_exists($this->parameterBag->uploadDir . '/' . $favicon->image->saved_name)) {
                        $json = FileSystem::read($this->parameterBag->uploadDir . '/' . $favicon->image->saved_name);
                        $json = preg_replace_callback(
                            '~("src"\s*:\s*")\s*\/?([^"]+?)\s*(")~',
                            function ($matches) {
                                $path = trim($matches[2]);

                                $image = $this->imageModel->getByOriginalName(basename($path));
                                if (!$image) {
                                    // nechat beze změny
                                    return $matches[0];
                                }

                                $url = $this->imageControlFactory->create()->getOriginal($image->id, true);

                                // url musí být validně escapované pro JSON string
                                $urlEsc = addcslashes($url, "\\\"");

                                return $matches[1] . $urlEsc . $matches[3];
                            },
                            $json
                        );
                        FileSystem::write($this->parameterBag->uploadDir . '/' . $favicon->image->saved_name, $json);
                    }
                }

                $favicons[] = json_encode(new FaviconDto(
                    rel: $favicon->rel,
                    type: $favicon->type,
                    sizes: $favicon->sizes,
                    href: $favicon->href,
                    name: $favicon->name,
                    content: $favicon->content,
                    tag: $favicon->tag,
                    image_to_attribute: $favicon->image_to_attribute,
                    imageId: $favicon->image_id,
                ));
            }
            return Arrays::map($favicons, fn(string $value) => FaviconDto::fromJson($value));
        });
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function render():void
    {
        $this->template->favicons = $this->getFavicons();
        $this->template->render(dirname(__FILE__) . '/default.latte');
    }
}