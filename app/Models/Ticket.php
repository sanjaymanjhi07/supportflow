<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_PENDING = 'pending';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    protected $fillable = [
        'tenant_id',
        'category_id',
        'requester_id',
        'assigned_to',
        'subject',
        'description',
        'status',
        'priority',
        'reference',
        'first_response_due_at',
        'resolution_due_at',
        'first_responded_at',
        'resolved_at',
        'sla_breached',
    ];

    protected $casts = [
        'first_response_due_at' => 'datetime',
        'resolution_due_at' => 'datetime',
        'first_responded_at' => 'datetime',
        'resolved_at' => 'datetime',
        'sla_breached' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            if (empty($ticket->reference)) {
                $ticket->reference = 'TCK-'.strtoupper(Str::random(8));
            }
            $ticket->status ??= self::STATUS_OPEN;
            $ticket->priority ??= 'normal';
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->orderBy('created_at');
    }

    public function isOverdueForFirstResponse(): bool
    {
        return is_null($this->first_responded_at)
            && $this->first_response_due_at
            && $this->first_response_due_at->isPast();
    }

    public function isOverdueForResolution(): bool
    {
        return is_null($this->resolved_at)
            && $this->resolution_due_at
            && $this->resolution_due_at->isPast();
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    public function scopeBreached($query)
    {
        return $query->where('sla_breached', true);
    }
}
