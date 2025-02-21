<?php

namespace App\Component\EditorJs;

use Nette\Utils\Html;
use Nette\Utils\Json;

class EditorJsRenderer
{
    /**
     * @param string $json
     * @return Html
     * @throws \Nette\Utils\JsonException
     * @throws \Exception
     */
    public function render(string $json):Html
    {
        $jsonDecode = Json::decode($json, true);
        $html = Html::el('script');
        foreach($jsonDecode['blocks'] as $block){
            switch ($block['type']){
                case 'paragraph': $html->addHtml(Html::el('p')->setText($block['data']['text'])); break;
                default: throw new \Exception(sprintf('Block %s not supported', $block['type']));
            }
        }
        return $html;
    }
}