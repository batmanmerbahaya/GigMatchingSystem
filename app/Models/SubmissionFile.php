<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Only the path is stored. The file itself lives on the private disk
 * and is served by WorkController::download to the two parties only.
 */
#[Fillable(['original_name', 'stored_path', 'file_type', 'file_size'])]
#[WithoutTimestamps]
class SubmissionFile extends Model
{
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function sizeLabel(): string
    {
        $kb = $this->file_size / 1024;

        return $kb >= 1024
            ? number_format($kb / 1024, 1).' MB'
            : number_format(max($kb, 1)).' KB';
    }
}
