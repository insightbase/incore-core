<?php

namespace App\Model\DoctrineEntity;

use App\Model\Enum\TranslateTypeEnum;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'translate')]
class Translate implements Entity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\Column(name: '`key`', type: 'text', length: 255)]
    public string $key;

    #[ORM\Column(type: 'string', length: 255)]
    public string $source;

    #[ORM\Column(type: 'string', length: 255, options: ['default' => TranslateTypeEnum::Text->value])]
    public string $type = TranslateTypeEnum::Text->value;

    public function setKey(string $key): self
    {
        $this->key = $key;
        return $this;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;
        return $this;
    }
}
