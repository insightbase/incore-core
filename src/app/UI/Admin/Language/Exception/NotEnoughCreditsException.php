<?php

namespace App\UI\Admin\Language\Exception;

/**
 * Překladové API odmítlo požadavek pro nedostatek kreditů (HTTP 402).
 *
 * Dědí z TranslateApiException, aby volající, kteří nedostatek kreditů neřeší
 * zvlášť, spadli do obecného hlášení chyby API.
 */
class NotEnoughCreditsException extends TranslateApiException {}
