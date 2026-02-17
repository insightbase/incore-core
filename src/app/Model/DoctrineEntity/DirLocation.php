<?php
declare(strict_types=1);

namespace App\Model\DoctrineEntity;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class DirLocation
{
    public function __construct(
        public string $dir,
    )
    {
        $this->dir = rtrim($this->dir, "/\\");
        if ($this->dir === '') {
            throw new \InvalidArgumentException('dir nesmí být prázdné');
        }
    }
}