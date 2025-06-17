<?php

namespace App\UI\Accessory\Admin\MainMenu;

class MainMenuSub
{
    private ?string $icon = null;
    private ?string $class = null;
    private bool $confirmDelete = false;

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

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setClass(?string $class): self
    {
        $this->class = $class;
        return $this;
    }

    public function isConfirmDelete(): bool
    {
        return $this->confirmDelete;
    }

    public function setConfirmDelete(bool $confirmDelete = true): self
    {
        $this->confirmDelete = $confirmDelete;
        return $this;
    }
}