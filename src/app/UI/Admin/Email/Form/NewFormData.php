<?php

namespace App\UI\Admin\Email\Form;

class NewFormData
{
    public string $name;
    public string $system_name;
    public string $subject;
    public string $text;
    public ?string $modifier;
}