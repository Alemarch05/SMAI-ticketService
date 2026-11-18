<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    public const STATUS_PENDIENTE_DIAGNOSTICO = 'PENDIENTE_DIAGNOSTICO';
    public const STATUS_ESCALADO = 'ESCALADO';
    public const STATUS_EN_PROCESO = 'EN_PROCESO';
    public const STATUS_RESUELTO = 'RESUELTO';
    public const STATUS_CERRADO = 'CERRADO';

    public const STATUSES = [
        self::STATUS_PENDIENTE_DIAGNOSTICO,
        self::STATUS_ESCALADO,
        self::STATUS_EN_PROCESO,
        self::STATUS_RESUELTO,
        self::STATUS_CERRADO,
    ];

    public const PRIORITY_BAJA = 'BAJA';
    public const PRIORITY_MEDIA = 'MEDIA';
    public const PRIORITY_ALTA = 'ALTA';

    public const PRIORITIES = [
        self::PRIORITY_BAJA,
        self::PRIORITY_MEDIA,
        self::PRIORITY_ALTA,
    ];

    protected $table = 'TK_tickets';

    protected $attributes = [
        'status' => self::STATUS_PENDIENTE_DIAGNOSTICO,
        'priority' => self::PRIORITY_MEDIA,
    ];

    protected $fillable = [
        'user_id',
        'assigned_to',
        'category_id',
        'location',
        'title',
        'description',
        'status',
        'priority',
        'level1_diagnostic',
        'cause',
        'solution',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'assigned_to' => 'integer',
            'category_id' => 'integer',
            'level1_diagnostic' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TicketLog::class, 'ticket_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function historicalCases(): HasMany
    {
        return $this->hasMany(HistoricalCase::class, 'ticket_id');
    }
}
