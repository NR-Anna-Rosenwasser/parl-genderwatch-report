<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Ramsey\Uuid\Uuid;

class Transcript extends Model
{
    use HasFactory, HasUuids;

    /**
     * Generate a new UUID for the model.
     */
    public function newUniqueId(): string
    {
        return (string) Uuid::uuid4();
    }

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'externalId',
        'text',
        'start',
        'end',
        'duration',
        'languageOfText',
        'business_id',
        'parl_session_id',
        'council_id',
        'member_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'start' => 'datetime',
            'end' => 'datetime',
            'business_id' => 'integer',
            'parl_session_id' => 'integer',
            'council_id' => 'integer',
            'member_id' => 'integer',
        ];
    }

    public function parlSession(): BelongsTo
    {
        return $this->belongsTo(ParlSession::class);
    }

    public function council(): BelongsTo
    {
        return $this->belongsTo(Council::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
