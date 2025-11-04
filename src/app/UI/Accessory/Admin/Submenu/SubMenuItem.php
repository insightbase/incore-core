<?php

namespace App\UI\Accessory\Admin\Submenu;

class SubMenuItem
{
    private bool $isPrimary = false;
    private ?string $modalId = null;
    private array $params = [];
    private bool $showInDropdown = false;
    private ?string $icon = null;
    private string $target = '_self';
    private bool $customLink = false;

    public function __construct(
        private string $name,
        private string $action,
    ) {}

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

    public function getModalId(): ?string
    {
        return $this->modalId;
    }

    public function setModalId(?string $modalId): self
    {
        $this->modalId = $modalId;

        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function addParam(string $param, string $value):self
    {
        $this->params[$param] = $value;
        return $this;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function isShowInDropdown(): bool
    {
        return $this->showInDropdown;
    }

    public function setShowInDropdown(bool $showInDropdown = true): self
    {
        $this->showInDropdown = $showInDropdown;
        return $this;
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

    public function getTarget(): string
    {
        return $this->target;
    }

    public function setTarget(string $target): self
    {
        $this->target = $target;
        return $this;
    }

    public function isCustomLink(): bool
    {
        return $this->customLink;
    }

    public function setCustomLink(bool $customLink = true): self
    {
        $this->customLink = $customLink;
        return $this;
    }
}
