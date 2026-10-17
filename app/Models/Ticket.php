<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    
    protected $table = 'TK_tickets';

    protected $fillable = [
        'user_id',
        'assigned_to',
        'category_id',
        'title',
        'description',
        'status',
        'level1_diagnostic',
        'cause',
        'solution',
    ];

    
    protected function casts(): array
    {
        return [
            'level1_diagnostic' => 'array',
        ];
    }
}