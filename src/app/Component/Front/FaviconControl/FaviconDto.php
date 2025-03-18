<?php

namespace App\Component\Front\FaviconControl;

class FaviconDto implements \JsonSerializable
{
    public function __construct(
        public ?string $rel,
        public ?string $type,
        public ?string $sizes,
        public ?string $href,
        public ?string $name,
        public ?string $content,
        public string $tag,
        public ?string $image_to_attribute,
        public ?int $imageId,
    )
    {
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        return new self(...$data);
    }
}