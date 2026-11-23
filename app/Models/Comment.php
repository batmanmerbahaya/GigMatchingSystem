<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * COMMENT. Plain text, never edited or deleted, so there is no updated_at.
 * A reply points at the comment it answers through parent_id.
 */
#[Fillable(['body', 'submission_id', 'parent_id'])]
class Comment extends Model
{
    public const UPDATED_AT = null;

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The comment this one replies to, if any.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
