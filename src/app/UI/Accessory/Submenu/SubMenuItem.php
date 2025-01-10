<?php

namespace App\UI\Accessory\Submenu;

class SubMenuItem
{
    private bool $isPrimary = false;
    private ?string $modalId = null;

    public function __construct(
        private string $name,
        private string $url,
    )
    {
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function setIsPrimary(bool $isPrimary = true): self
    {
        $this->isPrimary = $isPrimary;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getModalId(): ?string
    {
        return $this->modalId;
    }

    public function setModalId(?string $modalId): self
    {
        $this->modalId = $modalId;
        return $this;
    }
}