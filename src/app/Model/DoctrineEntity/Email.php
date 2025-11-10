<?php

namespace App\Model\DoctrineEntity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'email')]
class Email implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    public string $system_name;

    #[ORM\Column(type: 'string', length: 255)]
    public string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $text = null;

    #[ORM\Column(type: 'string', length: 255)]
    public string $subject;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $modifier = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    public ?string $template = null;

    #[Orm\Column(type: 'boolean')]
    public bool $forAdmin = false;

    public function setSystemName(string $system_name): self
    {
        $this->system_name = $system_name;

        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function setModifier(?string $modifier): self
    {
        $this->modifier = $modifier;

        return $this;
    }
}
