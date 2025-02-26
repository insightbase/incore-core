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
        $html = Html::el();
        foreach($jsonDecode['blocks'] as $block){
            switch ($block['type']){
                case 'paragraph': $html->addHtml(Html::el('p')->setHtml($block['data']['text'])); break;
                case 'list': (function() use ($block, $html){
                    if($block['data']['style'] === 'unordered'){
                        $mainTag = 'ul';
                    }else{
                        $mainTag = 'ol';
                    }
                    $htmlUl = Html::el($mainTag);
                    $this->addListItems($mainTag, $htmlUl, $block['data']['items']);
                    $html->addHtml($htmlUl);
                })(); break;
                default: throw new \Exception(sprintf('Block %s not supported', $block['type']));
            }
        }
        return $html;
    }

    private function addListItems(string $mainTag, Html $html, array $items):Html{
        foreach($items as $item){
            $li = Html::el('li')->setText($item['content']);
            if(count($item['items']) > 0){
                $subHtml = Html::el($mainTag);
                $this->addListItems($mainTag, $subHtml, $item['items']);
                $li->addHtml($subHtml);
            }
            $html->addHtml($li);
        }
        return $html;
    }
}