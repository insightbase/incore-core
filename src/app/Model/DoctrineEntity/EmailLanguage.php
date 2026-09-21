<?php

namespace App\Model\DoctrineEntity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'email_language')]
class EmailLanguage implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\ManyToOne(targetEntity: Email::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Email $email;

    #[ORM\ManyToOne(targetEntity: Language::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Language $language;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    public ?string $subject = null;

    #[ORM\Column(type: 'text', nullable: true, options: ['default' => null])]
    public ?string $text = null;
}
