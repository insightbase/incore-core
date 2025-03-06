<?php

namespace App\Model\DoctrineEntity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'translate')]
class Translate implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(name: '`key`', type: 'text', length: 255)]
    private string $key;

    #[ORM\Column(type: 'string', length: 255)]
    private string $source;

    public function setKey(string $key): self
    {
        $this->key = $key;
        return $this;
    }
}
