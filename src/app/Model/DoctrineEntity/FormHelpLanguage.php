<?php

namespace App\Model\DoctrineEntity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'form_help_language')]

class FormHelpLanguage implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\ManyToOne(targetEntity: Language::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Language $language;

    #[ORM\ManyToOne(targetEntity: FormHelp::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public FormHelp $form_help;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    public ?string $label_help = null;
}