<?php

namespace App\Model\DoctrineEntity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'language')]
class Language implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $locale;

    #[ORM\Column(type: 'string', length: 255)]
    private string $host;

    #[ORM\ManyToOne(targetEntity: Image::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Image $flag = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $is_default = false;

    #[ORM\Column(type: 'string', length: 10, unique: true)]
    private string $url;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $active = true;

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function setIsDefault(bool $is_default): self
    {
        $this->is_default = $is_default;

        return $this;
    }
}
