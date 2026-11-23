<?php

namespace App\Models;

use App\Enums\EntityType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CONTRACT_ENTITY profile, keyed by user_id.
 */
#[Table(name: 'contract_entities', key: 'user_id', incrementing: false, timestamps: false)]
#[Fillable(['entity_type', 'organisation_name'])]
class ContractEntity extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entity_type' => EntityType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
