<?php

declare(strict_types=1);

namespace App\UI\Accessory\Admin\Form;

trait EntityMaxLengthTrait
{
    /**
     * Nastaví maxlength text controlům podle délek sloupců dané Doctrine entity.
     *
     * @param class-string $entityClass
     */
    public function applyMaxLengthFromEntity(string $entityClass): static
    {
        MaxLengthApplier::apply(
            $this->getComponents(),
            EntityColumnLengthReader::getLengths($entityClass),
            fn (int $length): string => $this->translateMaxLengthMessage($length),
        );

        return $this;
    }

    /**
     * Přeloží hlášku o maximální délce; implementuje třída, která má translator.
     */
    abstract protected function translateMaxLengthMessage(int $length): string;
}
