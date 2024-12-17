<?php

namespace App\Component\Mail;

interface SenderFactory
{
    public function create(string $systemName):Sender;
}