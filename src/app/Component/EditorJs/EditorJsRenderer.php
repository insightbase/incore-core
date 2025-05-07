<?php

namespace App\Component\EditorJs;

use Nette\Utils\Html;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class EditorJsRenderer
{
    /**
     * @param string $json
     * @return Html
     */
    public function render(string $json):Html
    {
        try {
            $jsonDecode = Json::decode($json, true);
        } catch (JsonException $e) {
            return Html::fromHtml($json);
        }
        $html = Html::el();
        foreach($jsonDecode['blocks'] as $block){
            switch ($block['type']){
                case 'paragraph': $html->addHtml(Html::el('p')->setHtml($block['data']['text'])); break;
                case 'list': (function() use ($block, $html):void{
                    if($block['data']['style'] === 'unordered'){
                        $mainTag = 'ul';
                    }else{
                        $mainTag = 'ol';
                    }
                    $htmlUl = Html::el($mainTag);
                    $this->addListItems($mainTag, $htmlUl, $block['data']['items']);
                    $html->addHtml($htmlUl);
                })(); break;
                case 'raw': $html->addHtml(Html::el()->setHtml($block['data']['html'])); break;
                case 'header': $html->addHtml(Html::el('h' . $block['data']['level'])->setHtml($block['data']['text'])); break;
                case 'table': (function() use ($block, $html):void{
                    $table = Html::el('table');
                    $isFirst = true;
                    foreach($block['data']['content'] as $row){
                        $tr = Html::el('tr');
                        foreach($row as $column){
                            if($isFirst && $block['data']['withHeadings']){
                                $tr->addHtml(Html::el('th')->setText($column));
                            }else {
                                $tr->addHtml(Html::el('td')->setText($column));
                            }
                        }
                        $table->addHtml($tr);
                        $isFirst = false;
                    }
                    $html->addHtml($table);
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