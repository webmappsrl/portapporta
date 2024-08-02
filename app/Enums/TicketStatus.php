<?php

namespace App\Enums;

enum TicketStatus: string
{

    case New = 'new';
    case Readed = 'readed';
    case Execute = 'execute';
    case Collected = 'collected';
    case Deleted = 'deleted';
    case Done = 'done';

    public static function toArray()
    {
        return [
            'new' => 'new',
            'readed' => 'readed',
            'execute' => 'execute',
            'collected' => 'collected',
            'deleted' => 'deleted',
            'done' => 'done',
        ];
    }
}
