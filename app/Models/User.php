<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\ApplicationStage;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * USER. Column mapping to the ERD: id = user_id, name = full_name,
 * password = password_hash.
 *
 * 'role' is only written from validated registration data. account_status
 * and deleted_at are never mass assignable.
 */
#[Fillable(['name', 'email', 'password', 'phone_number', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'deleted_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'account_status' => AccountStatus::class,
        ];
    }

    public function isGigWorker(): bool
    {
        return $this->role === UserRole::GigWorker;
    }

    public function isContractEntity(): bool
    {
        return $this->role === UserRole::ContractEntity;
    }

    public function isDeleted(): bool
    {
        return $this->account_status === AccountStatus::Deleted;
    }

    public function gigWorker(): HasOne
    {
        return $this->hasOne(GigWorker::class, 'user_id');
    }

    public function contractEntity(): HasOne
    {
        return $this->hasOne(ContractEntity::class, 'user_id');
    }

    /**
     * Jobs posted by this Contract Entity.
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class, 'user_id');
    }

    /**
     * Applications made by this Gig Worker.
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'user_id');
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }

    /**
     * Name shown to other users: the organisation name for a business,
     * otherwise the person's name.
     */
    public function displayName(): string
    {
        if ($this->isContractEntity() && filled($this->contractEntity?->organisation_name)) {
            return $this->contractEntity->organisation_name;
        }

        return $this->name;
    }

    /**
     * Average rating received, calculated when needed (spec section 9).
     */
    public function averageRating(): ?float
    {
        $average = $this->reviewsReceived()->avg('rating');

        return $average === null ? null : round((float) $average, 1);
    }

    /**
     * Number of jobs this user finished with payment confirmed.
     */
    public function completedJobCount(): int
    {
        return $this->applicationsAsParty()
            ->whereIn('stage', ApplicationStage::finishedStages())
            ->count();
    }

    /**
     * True while the user has accepted work that is not yet Confirmed.
     * This blocks account deletion (spec section 10).
     */
    public function hasUnfinishedWork(): bool
    {
        return $this->applicationsAsParty()
            ->whereIn('stage', ApplicationStage::unfinishedStages())
            ->exists();
    }

    /**
     * Applications where this user is one of the two parties.
     */
    public function applicationsAsParty(): Builder
    {
        if ($this->isGigWorker()) {
            return Application::query()->where('user_id', $this->id);
        }

        return Application::query()->whereHas('job', fn ($query) => $query->where('user_id', $this->id));
    }

    /**
     * Deleted accounts have a placeholder address, so mail is never sent.
     */
    public function routeNotificationForMail(): ?string
    {
        return $this->isDeleted() ? null : $this->email;
    }
}
