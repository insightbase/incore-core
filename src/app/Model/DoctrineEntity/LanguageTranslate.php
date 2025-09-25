<?php

namespace App\Model\DoctrineEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'language_translate')]
class LanguageTranslate implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\Column(type: 'integer')]
    public int $drop_core_id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public User $user;

    #[ORM\ManyToOne(targetEntity: Language::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Language $language;

    #[ORM\Column(type: 'datetime')]
    public \DateTime $datetime;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['default' => null])]
    public ?\DateTime $finished = null;
}