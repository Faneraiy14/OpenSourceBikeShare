<?php

declare(strict_types=1);

namespace BikeShare\Enum;

enum CouponStatus: int
{
    case ACTIVE = 0;
    case SOLD = 1;
    case USED = 2;
}
