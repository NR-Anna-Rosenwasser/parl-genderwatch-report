<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Ramsey\Uuid\Uuid;

class Member extends Model
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
        'externalPersonId',
        'isActive',
        'firstName',
        'lastName',
        'genderAsString',
        'dateJoining',
        'dateLeaving',
        'dateElection',
        'party_id',
        'parl_group_id',
        'canton_id',
        'council_id',
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
            'isActive' => 'boolean',
            'dateJoining' => 'datetime',
            'dateLeaving' => 'datetime',
            'dateElection' => 'datetime',
            'party_id' => 'integer',
            'parl_group_id' => 'integer',
            'canton_id' => 'integer',
            'council_id' => 'integer',
        ];
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function parlGroup(): BelongsTo
    {
        return $this->belongsTo(ParlGroup::class);
    }

    public function canton(): BelongsTo
    {
        return $this->belongsTo(Canton::class);
    }

    public function council(): BelongsTo
    {
        return $this->belongsTo(Council::class);
    }

    public function parlSessions(): BelongsToMany
    {
        return $this->belongsToMany(ParlSession::class);
    }
}
