<?php

namespace App\Model\DoctrineEntity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'form_help')]
class FormHelp implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\Column(type: 'string', length: 255)]
    public string $presenter;

    #[ORM\Column(type: 'string', length: 255)]
    public string $input;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    public ?string $label_help = null;
}