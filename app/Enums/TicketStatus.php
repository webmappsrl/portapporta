<?php

namespace App\Enums;

enum TicketStatus: string
{

    case New = 'new';
    case Readed = 'readed';
    case Execute = 'execute';
    case Deleted = 'deleted';
    case Done = 'done';

    public static function toArray()
    {
        return [
            'new' => 'new',
            'readed' => 'readed',
            'execute' => 'execute',
            'deleted' => 'deleted',
            'done' => 'done',
        ];
    }
}
