<?php

namespace App\Component\Image;

class ImageDto implements \JsonSerializable
{
    public function __construct(
        public string $saved_name,
        public ?string $alt = null,
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