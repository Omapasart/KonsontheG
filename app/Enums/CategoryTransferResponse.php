<?php

namespace App\Enums;

enum CategoryTransferResponse: string
{
    case Accepted = 'accepted';
    case Declined = 'declined';
}
