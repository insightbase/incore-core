<?php

namespace App\Component\Mail;

use App\Model\Entity\EmailEntity;

/**
 * Šablona emailu připravená k vykreslení – texty už jsou v jazyce příjemce,
 * s fallbackem na výchozí jazyk.
 */
readonly class EmailDto
{
    public function __construct(
        public int $id,
        public string $system_name,
        public string $name,
        public string $subject,
        public ?string $text,
        public ?string $modifier,
        public ?string $template,
        public bool $forAdmin,
    ) {
    }

    /**
     * @param EmailEntity $email
     */
    public static function create(\Nette\Database\Table\ActiveRow $email, ?string $subject = null, ?string $text = null): self
    {
        return new self(
            id: $email->id,
            system_name: $email->system_name,
            name: $email->name,
            subject: null !== $subject && '' !== $subject ? $subject : $email->subject,
            text: null !== $text && '' !== $text ? $text : $email->text,
            modifier: $email->modifier,
            template: $email->template,
            forAdmin: $email->forAdmin,
        );
    }
}
