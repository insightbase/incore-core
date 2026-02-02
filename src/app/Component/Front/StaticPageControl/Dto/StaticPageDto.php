<?php

namespace App\Component\Front\StaticPageControl\Dto;

class StaticPageDto
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $title,
        public ?string $description,
        public ?string $keywords,
        public ?string $content,
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