<?php

namespace App\Enums;

enum UserType: string
{
    case MEMBER = 'member';
    case BOARD = 'board';
    case EMPLOYEE = 'employee';
    case PENDING_MEMBER = 'pending_member';
}
