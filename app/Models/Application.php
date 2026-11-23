<?php

namespace App\Models;

use App\Enums\ApplicationStage;
use App\Enums\ApprovalMethod;
use App\Enums\SubmissionStatus;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * APPLICATION. The accepted application acts as the contract between the
 * Gig Worker (user_id) and the Contract Entity who owns the job.
 */
#[Fillable(['message'])]
#[WithoutTimestamps]
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stage' => ApplicationStage::class,
            'approval_method' => ApprovalMethod::class,
            'applied_at' => 'datetime',
            'accepted_at' => 'datetime',
            'completed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'rated_at' => 'datetime',
            'ended_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * The Gig Worker who applied.
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Move to a new stage and record its timestamp.
     */
    public function moveTo(ApplicationStage $stage): void
    {
        $column = $stage->timestampColumn();

        $this->stage = $stage;
        $this->setAttribute($column, now());
        $this->save();
    }

    public function isAt(ApplicationStage ...$stages): bool
    {
        return in_array($this->stage, $stages, true);
    }

    /**
     * Accepted or later: phones revealed, work and comments visible.
     */
    public function isContract(): bool
    {
        return $this->isAt(...ApplicationStage::contractStages());
    }

    /**
     * The comment thread is open from Accepted until payment is Confirmed.
     */
    public function threadIsOpen(): bool
    {
        return $this->isAt(ApplicationStage::Accepted, ApplicationStage::Completed);
    }

    public function hasApprovedWork(): bool
    {
        return $this->approved_at !== null;
    }

    public function hasPendingSubmission(): bool
    {
        return $this->submissions()->where('status', SubmissionStatus::Pending->value)->exists();
    }

    public function isParty(User $user): bool
    {
        return $user->id === $this->user_id || $user->id === $this->job->user_id;
    }

    /**
     * The other party of the job, seen from the given user.
     */
    public function otherParty(User $user): User
    {
        return $user->id === $this->user_id ? $this->job->employer : $this->worker;
    }

    /**
     * Record that work was approved, manually or by the scheduled task.
     */
    public function markWorkApproved(ApprovalMethod $method): void
    {
        $this->approved_at = now();
        $this->approval_method = $method;
        $this->save();
    }
}
