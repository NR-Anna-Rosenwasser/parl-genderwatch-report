<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Ramsey\Uuid\Uuid;

class ParlGroup extends Model
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
        'number',
        'isActive',
        'code',
        'name',
        'abbreviation',
        'nameUsedSince',
        'modified',
        'colour',
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
            'nameUsedSince' => 'datetime',
            'modified' => 'datetime',
        ];
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }
}
