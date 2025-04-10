<?php

namespace App\Event\Language;

use App\Event\Event;
use App\Model\Entity\LanguageEntity;
use Nette\Database\Table\ActiveRow;

/**
 * @property LanguageEntity $originalLanguage
 * @property LanguageEntity $newLanguage
 */
class ChangeDefaultEvent implements Event
{
    public function __construct(
        public ActiveRow $originalLanguage,
        public ActiveRow $newLanguage,
    ){}
}