<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricalCase extends Model
{
    use HasFactory;

    protected $table = 'TK_historical_cases';

    protected $fillable = [
        'ticket_id',
        'problem_type',
        'cause',
        'solution',
    ];
}