<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketDiagnostic extends Model
{
    protected $table = 'TK_ticket_diagnostics';

    protected $fillable = [
        'ticket_id',
        'category_id',
        'decisions',
    ];

    protected $casts = [
        'decisions' => 'array',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}