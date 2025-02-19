<?php

namespace App\UI\Accessory\Admin\Submenu;

class SubMenuItem
{
    private bool $isPrimary = false;
    private ?string $modalId = null;
    private array $params = [];

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
}
