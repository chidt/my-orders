<?php

namespace App\Enums;

enum CustomerType: int
{
    case INDIVIDUAL = 1;
    case BUSINESS = 2;
    case CORPORATE = 3;
}
