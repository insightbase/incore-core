<?php

namespace App\UI\Admin\Role\Form;

class AuthorizationSetData
{
    /** @var list<int> */
    public array $privileges;

    /** Jen u modulu s providerem položek; jinak zůstane výchozí hodnota. */
    public bool $itemRestricted = false;

    /** @var list<int> */
    public array $items = [];
}
