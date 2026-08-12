<?php

namespace App\Support;

enum PasswordResetResult: string
{
    case Sent = 'sent';
    case UserNotFound = 'user_not_found';
    case Inactive = 'inactive';
    case MissingEmail = 'missing_email';
    case InvalidToken = 'invalid_token';
}
