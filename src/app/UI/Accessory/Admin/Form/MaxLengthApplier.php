<?php

declare(strict_types=1);

namespace App\UI\Accessory\Admin\Form;

use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\TextBase;
use Nette\Forms\Form;

final class MaxLengthApplier
{
    /**
     * @param iterable<mixed> $controls
     * @param array<string, int> $lengths
     * @param callable(int): string $messageFactory
     */
    public static function apply(iterable $controls, array $lengths, callable $messageFactory): void
    {
        foreach ($controls as $control) {
            if (!$control instanceof TextBase) {
                continue;
            }

            $name = $control->getName();
            if ($name === null || !array_key_exists($name, $lengths)) {
                continue;
            }

            if (self::findMaxLength($control) !== null) {
                continue;
            }

            $length = $lengths[$name];
            $control->addRule(Form::MaxLength, $messageFactory($length), $length);
        }
    }

    /**
     * @param callable(int): string $messageFactory
     */
    public static function copyMaxLength(TextBase $source, TextBase $target, callable $messageFactory): void
    {
        $length = self::findMaxLength($source);
        if ($length === null || self::findMaxLength($target) !== null) {
            return;
        }

        $target->addRule(Form::MaxLength, $messageFactory($length), $length);
    }

    public static function findMaxLength(BaseControl $control): ?int
    {
        foreach ($control->getRules() as $rule) {
            if ($rule->validator === Form::MaxLength && is_int($rule->arg)) {
                return $rule->arg;
            }
        }

        return null;
    }
}
