<?php

namespace App\UI\Accessory\MainMenu;

class MainMenuSub
{
    private ?string $icon = null;

    public function __construct(
        private readonly string $action,
        private readonly string $title,
        private readonly array  $params,
    )
    {
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }
}