<?php

namespace App\Model\DoctrineEntity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'translate_language')]
class TranslateLanguage implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $value;

    #[ORM\ManyToOne(targetEntity: Language::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Language $language;

    #[ORM\ManyToOne(targetEntity: Translate::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Translate $translate;
}
