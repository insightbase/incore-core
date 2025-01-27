<?php

namespace App\Component\Mail;

enum SystemNameEnum: string
{
    case ForgotPassword = 'forgotPassword';
    case TestEmail = 'testEmail';
}
