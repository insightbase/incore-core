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
                                $tr->addHtml(Html::el('th')->setHtml($column));
                            }else {
                                $tr->addHtml(Html::el('td')->setHtml($column));
                            }
                        }
                        $table->addHtml($tr);
                        $isFirst = false;
                    }
                    $html->addHtml($table);
                })(); break;
                case 'faq': (function() use ($block, $html):void{
                    $faq = Html::el('div', ['class' => 'faq']);
                    foreach($block['data']['items'] as $item){
                        $accordionItem = Html::el('div', ['class' => 'accordion-item'])
                            ->setAttribute('data-accordion-item', true);

                        $banner = Html::el('a', [
                            'href' => '#',
                            'target' => '_self',
                            'class' => 'accordion-item__banner',
                        ])->setAttribute('data-accordion-item-banner', true);
                        $bannerInner = Html::el('div', ['class' => 'flexbox flexbox--align-start flexbox--space-between']);
                        $bannerInner->addHtml(Html::el('p', ['class' => 'fw-600 fs-20'])->setHtml($item['question']));
                        $bannerInner->addHtml(Html::el('span', ['class' => 'icon'])->setText('+'));
                        $banner->addHtml($bannerInner);
                        $accordionItem->addHtml($banner);

                        $content = Html::el('div', ['class' => 'accordion-item__content'])
                            ->setAttribute('data-accordion-item-content', true);
                        $contentInner = Html::el('div');
                        $contentInner->addHtml(Html::el('p')->setHtml($item['answer']));
                        $content->addHtml($contentInner);
                        $accordionItem->addHtml($content);

                        $faq->addHtml($accordionItem);
                    }
                    $html->addHtml($faq);
                })(); break;
                case 'citation': (function() use ($block, $html):void{
                    $citation = Html::el('div', ['class' => 'citation']);
                    $inner = Html::el('div', ['class' => 'fs-20 fw-700']);
                    $inner->addHtml(Html::el('p')->setHtml($block['data']['text']));
                    if(!empty($block['data']['author'])){
                        $inner->addHtml(Html::el('p', ['class' => 'text-right fs-14 fw-500'])->setText($block['data']['author']));
                    }
                    $citation->addHtml($inner);
                    $html->addHtml($citation);
                })(); break;
                case 'spotify': (function() use ($block, $html):void{
                    $info = $this->parseSpotify($block['data']['url'] ?? '');
                    if($info === null){
                        return;
                    }
                    $height = $block['data']['height'] ?? (in_array($info['type'], ['episode', 'show'], true) ? 232 : 152);
                    $iframe = Html::el('iframe', [
                        'src' => sprintf('https://open.spotify.com/embed/%s/%s', $info['type'], $info['id']),
                        'width' => '100%',
                        'height' => (string)$height,
                        'frameborder' => '0',
                        'allow' => 'autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture',
                        'loading' => 'lazy',
                        'style' => 'border-radius:12px;border:0;',
                    ]);
                    $wrapper = Html::el('div', ['class' => 'spotify-wrapper']);
                    $wrapper->addHtml($iframe);
                    $html->addHtml($wrapper);
                })(); break;
                case 'youtube': (function() use ($block, $html):void{
                    $data = $block['data'];
                    $type = $data['type'] ?? null;
                    $id = $data['id'] ?? null;
                    $start = isset($data['start']) ? (int)$data['start'] : null;
                    if($id === null || $id === ''){
                        $info = $this->parseYoutube($data['url'] ?? '');
                        if($info === null){
                            return;
                        }
                        $type = $info['type'];
                        $id = $info['id'];
                        $start = $info['start'];
                    }
                    $iframe = Html::el('iframe', [
                        'src' => $this->buildYoutubeEmbedSrc($type, $id, $start),
                        'allow' => 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share',
                        'allowfullscreen' => true,
                        'loading' => 'lazy',
                        'style' => 'position:absolute;inset:0;width:100%;height:100%;border:0;',
                    ]);
                    $ratio = Html::el('div', ['style' => 'position:relative;width:100%;padding-top:56.25%;border-radius:12px;overflow:hidden;']);
                    $ratio->addHtml($iframe);
                    $wrapper = Html::el('div', ['class' => 'video-wrapper']);
                    $wrapper->addHtml($ratio);
                    $html->addHtml($wrapper);
                })(); break;
                case 'audio': (function() use ($block, $html):void{
                    $url = $block['data']['file']['url'] ?? null;
                    if($url === null || $url === ''){
                        return;
                    }
                    $audio = Html::el('audio')
                        ->setAttribute('controls', true)
                        ->setAttribute('src', $url)
                        ->setText('Váš prohlížeč nepodporuje přehrávání zvuku.');
                    $wrapper = Html::el('div', ['class' => 'news-audio']);
                    $wrapper->addHtml(Html::el('div')->addHtml($audio));
                    $html->addHtml($wrapper);
                })(); break;
                case 'gallery': (function() use ($block, $html):void{
                    $files = $block['data']['files'] ?? [];
                    if(count($files) === 0){
                        return;
                    }
                    $gallery = Html::el('div', ['class' => 'gallery']);
                    foreach($files as $file){
                        $url = $file['url'] ?? null;
                        if($url === null || $url === ''){
                            continue;
                        }
                        $link = Html::el('a', [
                            'href' => $url,
                            'class' => 'gallery__item',
                            'target' => '_blank',
                            'rel' => 'noopener noreferrer',
                        ]);
                        $link->addHtml(Html::el('img', ['src' => $url, 'alt' => '', 'loading' => 'lazy']));
                        $gallery->addHtml($link);
                    }
                    $html->addHtml($gallery);
                })(); break;
                case 'multiImage': (function() use ($block, $html):void{
                    $images = $block['data']['images'] ?? [];
                    if(count($images) === 0){
                        return;
                    }
                    $perRow = (int)($block['data']['perRow'] ?? 3);
                    if($perRow < 1){
                        $perRow = 3;
                    }
                    foreach(array_chunk($images, $perRow) as $row){
                        $cols = implode(' ', array_map(
                            fn(array $img):string => ($img['width'] ?? round(100 / count($row))) . 'fr',
                            $row
                        ));
                        $rowEl = Html::el('div', [
                            'class' => 'multi-image-row',
                            'style' => 'display:grid;gap:8px;grid-template-columns:' . $cols . ';',
                        ]);
                        foreach($row as $img){
                            $url = $img['url'] ?? null;
                            if($url === null || $url === ''){
                                continue;
                            }
                            $imgEl = Html::el('img', ['src' => $url, 'alt' => $img['alt'] ?? '', 'loading' => 'lazy']);
                            $figure = Html::el('figure', ['class' => 'multi-image-row__item', 'style' => 'margin:0;']);
                            if(!empty($img['link'])){
                                $figure->addHtml(Html::el('a', ['href' => $img['link'], 'target' => '_blank', 'rel' => 'noopener noreferrer'])->addHtml($imgEl));
                            }else{
                                $figure->addHtml($imgEl);
                            }
                            $rowEl->addHtml($figure);
                        }
                        $html->addHtml($rowEl);
                    }
                })(); break;
                default: throw new \Exception(sprintf('Block %s not supported', $block['type']));
            }
        }
        return $html;
    }

    /**
     * Extracts Spotify type and id from a URL, mirroring the editor tool's extractInfo().
     * Supports /track/:id, /album/:id, /playlist/:id, /episode/:id, /show/:id with optional intl- locale prefix.
     *
     * @param string $rawUrl
     * @return array{type: string, id: string}|null
     */
    private function parseSpotify(string $rawUrl):?array
    {
        $parsed = parse_url($rawUrl);
        if($parsed === false || empty($parsed['host']) || empty($parsed['path'])){
            return null;
        }
        $host = strtolower($parsed['host']);
        if($host !== 'spotify.com' && !str_ends_with($host, '.spotify.com')){
            return null;
        }
        $parts = array_values(array_filter(explode('/', $parsed['path']), fn(string $p):bool => $p !== ''));
        $start = (isset($parts[0]) && str_starts_with($parts[0], 'intl-')) ? 1 : 0;
        $type = $parts[$start] ?? null;
        $id = $parts[$start + 1] ?? null;
        if($type === null || $id === null){
            return null;
        }
        if(!in_array($type, ['track', 'album', 'playlist', 'episode', 'show'], true)){
            return null;
        }
        return ['type' => $type, 'id' => $id];
    }

    /**
     * Extracts YouTube type, id and start from a URL, mirroring the editor tool's extractInfo().
     * Supports watch?v=, youtu.be/, /shorts/, /embed/, /live/ and playlists via the list param.
     *
     * @param string $rawUrl
     * @return array{type: string, id: string, start: int|null}|null
     */
    private function parseYoutube(string $rawUrl):?array
    {
        $parsed = parse_url($rawUrl);
        if($parsed === false || empty($parsed['host'])){
            return null;
        }
        $host = strtolower((string)preg_replace('/^www\./i', '', $parsed['host']));
        if(!str_ends_with($host, 'youtube.com') && $host !== 'youtu.be'){
            return null;
        }
        parse_str($parsed['query'] ?? '', $query);
        if(!empty($query['list'])){
            return ['type' => 'playlist', 'id' => (string)$query['list'], 'start' => null];
        }
        $path = $parsed['path'] ?? '';
        $segments = array_values(array_filter(explode('/', $path), fn(string $p):bool => $p !== ''));
        $videoId = null;
        if($path === '/watch'){
            $videoId = $query['v'] ?? null;
        }
        if($videoId === null && $host === 'youtu.be'){
            $videoId = $segments[0] ?? null;
        }
        if($videoId === null && (str_starts_with($path, '/shorts/') || str_starts_with($path, '/embed/') || str_starts_with($path, '/live/'))){
            $videoId = $segments[1] ?? null;
        }
        if($videoId === null || $videoId === ''){
            return null;
        }
        return ['type' => 'video', 'id' => (string)$videoId, 'start' => $this->parseYoutubeStart($query)];
    }

    /**
     * Parses a YouTube start time from the start/t query params (formats: 90, 90s, 1m30s, 1h2m3s).
     *
     * @param array<string, mixed> $query
     * @return int|null
     */
    private function parseYoutubeStart(array $query):?int
    {
        $startParam = $query['start'] ?? $query['t'] ?? null;
        if($startParam === null || $startParam === ''){
            return null;
        }
        $str = (string)$startParam;
        if(preg_match('/^\d+$/', $str)){
            return (int)$str;
        }
        if(preg_match('/^(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?$/i', $str, $m) && ($m[0] !== '')){
            return ((int)($m[1] ?? 0)) * 3600 + ((int)($m[2] ?? 0)) * 60 + ((int)($m[3] ?? 0));
        }
        return null;
    }

    /**
     * Builds a YouTube embed src, mirroring the editor tool's buildEmbedSrc().
     *
     * @param string|null $type
     * @param string $id
     * @param int|null $start
     * @return string
     */
    private function buildYoutubeEmbedSrc(?string $type, string $id, ?int $start):string
    {
        if($type === 'playlist'){
            return 'https://www.youtube.com/embed/videoseries?list=' . rawurlencode($id);
        }
        $src = 'https://www.youtube.com/embed/' . rawurlencode($id);
        if($start !== null && $start > 0){
            $src .= '?start=' . $start;
        }
        return $src;
    }

    private function addListItems(string $mainTag, Html $html, array $items):Html{
        foreach($items as $item){
            $li = Html::el('li')->setHtml($item['content']);
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