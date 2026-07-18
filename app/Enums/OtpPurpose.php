<?php

namespace App\Enums;

enum OtpPurpose: string
{
    case Login = 'login';
    case PasswordChange = 'password_change';
}
