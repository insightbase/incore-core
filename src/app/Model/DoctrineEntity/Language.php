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
    public int $id;

    #[ORM\Column(type: 'string', length: 255)]
    public string $name;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    public string $locale;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $host = null;

    #[ORM\ManyToOne(targetEntity: Image::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    public ?Image $flag = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    public bool $is_default = false;

    #[ORM\Column(type: 'string', length: 10, unique: true)]
    public string $url;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    public bool $active = true;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    public ?string $drop_core_id = null;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['default' => null])]
    public ?\DateTime $drop_core_last_call_date = null;

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
