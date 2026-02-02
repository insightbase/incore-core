<?php

namespace App\Model\DoctrineEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'static_page_language')]
class StaticPageLanguage implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\ManyToOne(targetEntity: Language::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Language $language;

    #[ORM\ManyToOne(targetEntity: StaticPage::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public StaticPage $static_page;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $name = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $slug = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $description = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $keywords = null;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $content = null;
}