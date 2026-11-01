<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketLog extends Model
{
    use HasFactory;

    protected $table = 'TK_ticket_logs';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'action',
        'comment',
    ];
}