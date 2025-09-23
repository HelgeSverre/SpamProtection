<?php

declare(strict_types=1);

namespace Helge\SpamProtection;

enum Types: string
{
    case EMAIL = 'email';
    case USERNAME = 'username';
    case IP = 'ip';
}
