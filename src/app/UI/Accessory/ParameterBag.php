<?php

namespace App\UI\Accessory;

class ParameterBag
{
    public function __construct(
        public string $wwwDir,
        public bool $debugMode,
        public string $uploadDir,
        public string $previewDir,
        public string $previewWwwDir,
        public string $tempDir,
        public string $appDir,
        public string $encryptionKey,
        public bool $autoGenerateEntities,
    ) {}
}
