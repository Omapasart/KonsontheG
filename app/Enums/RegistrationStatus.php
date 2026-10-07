<?php

namespace App\Enums;

enum RegistrationStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
