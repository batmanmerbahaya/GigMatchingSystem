<?php

namespace App\Models;

use App\Enums\ApplicationStage;
use App\Enums\JobCategory;
use App\Enums\JobStatus;
use App\Enums\PayType;
use Database\Factories\JobFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * JOB. user_id is the Contract Entity who posted it. user_id and status
 * are deliberately not fillable: only authorised controller code sets them.
 */
#[Fillable([
    'title',
    'description',
    'category',
    'location_id',
    'pay_rate',
    'pay_type',
    'start_date',
    'end_date',
])]
class Job extends Model
{
    /** @use HasFactory<JobFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => JobCategory::class,
            'pay_type' => PayType::class,
            'status' => JobStatus::class,
            'pay_rate' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * The Contract Entity who posted the job.
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /**
     * The accepted application, which acts as the contract.
     */
    public function contract(): HasOne
    {
        return $this->hasOne(Application::class)
            ->whereIn('stage', ApplicationStage::contractStages());
    }

    /**
     * Scope: jobs that appear in search. Hidden and closed jobs never do.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', JobStatus::Open->value);
    }

    public function isOpen(): bool
    {
        return $this->status === JobStatus::Open;
    }

    public function isHidden(): bool
    {
        return $this->status === JobStatus::Hidden;
    }

    public function payLabel(): string
    {
        return config('gigs.currency').' '.number_format((float) $this->pay_rate, 2).' '.$this->pay_type->suffix();
    }

    /**
     * Number of calendar days the job runs, counting both ends.
     */
    public function durationDays(): int
    {
        return (int) $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function dateRangeLabel(): string
    {
        if ($this->start_date->isSameDay($this->end_date)) {
            return $this->start_date->format('D, j M Y');
        }

        return $this->start_date->format('j M').' to '.$this->end_date->format('j M Y');
    }

    /**
     * Close the listing and cancel every application still at Applied.
     * Used by Close Job (UC12) and Delete Account (UC21). The caller sends
     * the cancellation emails after the transaction commits.
     *
     * @return Collection<int, Application> the cancelled applications
     */
    public function closeListing(): Collection
    {
        $this->status = JobStatus::Closed;
        $this->save();

        $cancelled = $this->applications()
            ->where('stage', ApplicationStage::Applied->value)
            ->get();

        foreach ($cancelled as $application) {
            $application->moveTo(ApplicationStage::Cancelled);
        }

        return $cancelled;
    }
}
