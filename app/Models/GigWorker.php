<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GIG_WORKER profile, keyed by user_id.
 */
#[Table(name: 'gig_workers', key: 'user_id', incrementing: false, timestamps: false)]
#[Fillable(['bio'])]
class GigWorker extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
