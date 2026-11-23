@extends('layouts.app')

@section('title', 'History')
@section('page-title', 'History')
@section('page-subtitle', 'Finished jobs and applications that ended. Once payment is confirmed, you can rate the employer here.')

@section('content')
    @php
        use App\Enums\ApplicationStage;

        $user = auth()->user();
        $filters = ['all' => 'All', 'done' => 'Completed', 'ended' => 'Ended early'];
        $notes = [
            ApplicationStage::Withdrawn->value => 'You withdrew this application.',
            ApplicationStage::Rejected->value => 'The employer chose not to go ahead with this application.',
            ApplicationStage::Cancelled->value => 'The job was closed or an account was deleted.',
        ];
        $lastMonth = null;
    @endphp

    <div class="sum-row">
        <div class="sum-tile">
            <span class="sum-ic s-completed" aria-hidden="true"><x-icon name="check-circle" /></span>
            <div class="sum-text"><b>{{ $doneCount }}</b><span>{{ Str::plural('Job', $doneCount) }} completed</span></div>
        </div>
        <div class="sum-tile">
            <span class="sum-ic s-rating" aria-hidden="true"><x-icon name="star" /></span>
            <div class="sum-text">
                @if ($averageRating !== null)
                    <b>{{ number_format($averageRating, 1) }} / 5</b>
                    <span>Your rating from {{ $reviewCount }} {{ Str::plural('review', $reviewCount) }}</span>
                @else
                    <b>&ndash;</b>
                    <span>No ratings yet</span>
                @endif
            </div>
        </div>
        <div class="sum-tile">
            <span class="sum-ic s-ended" aria-hidden="true"><x-icon name="x-circle" /></span>
            <div class="sum-text"><b>{{ $endedCount }}</b><span>Ended early</span></div>
        </div>
    </div>

    <div class="panel h-panel">
        <div class="h-bar">
            <h2 class="h-bar-title">Past applications</h2>
            <nav class="seg" aria-label="Show">
                @foreach ($filters as $key => $label)
                    <a href="{{ route('worker.history', $key === 'all' ? [] : ['show' => $key]) }}" data-skeleton="table"
                       @if ($show === $key) aria-current="true" @endif>{{ $label }}</a>
                @endforeach
            </nav>
        </div>

        @if ($applications->isEmpty())
            <div class="empty-state">
                <span class="tile t-all" aria-hidden="true"><x-icon name="history" /></span>
                <h3 class="empty-state-title">Nothing here yet</h3>
                <p class="empty-state-text">Jobs appear here once payment is confirmed or the application ends.</p>
            </div>
        @else
            <div class="h-head" aria-hidden="true"><span></span><span>Job</span><span>Pay</span><span>Stage</span><span>Date</span><span></span></div>

            @foreach ($applications as $application)
                @php
                    $job = $application->job;
                    $employerName = $job->employer->displayName();
                    $date = $application->ended_at ?? $application->rated_at ?? $application->confirmed_at ?? $application->applied_at;
                    $month = $date?->format('F Y');
                    $myReview = $application->reviews->firstWhere('reviewer_id', $user->id);
                    $theirReview = $application->reviews->first(fn ($review) => $review->reviewer_id !== $user->id);
                    $canRate = $user->can('review', $application);
                @endphp

                @if ($month !== $lastMonth)
                    <h3 class="h-month">{{ $month }}</h3>
                    @php $lastMonth = $month; @endphp
                @endif

                <div class="h-row">
                    <x-cat-tile :category="$job->category" />
                    <div class="h-main">
                        <span class="h-title">{{ $job->title }}</span>
                        <span class="h-sub">{{ $employerName }} &middot; {{ $job->location->label() }}</span>
                        <span class="h-note">
                            @if (isset($notes[$application->stage->value]))
                                <x-icon name="info" />{{ $notes[$application->stage->value] }}
                            @else
                                @if ($myReview)
                                    You rated {{ $employerName }} <x-stars :rating="$myReview->rating" />
                                @else
                                    <x-icon name="check-circle" />{{ $employerName }} confirmed your payment.
                                @endif
                                &middot;
                                @if ($theirReview)
                                    {{ $employerName }} rated you <x-stars :rating="$theirReview->rating" />
                                @else
                                    {{ $employerName }} has not rated you yet.
                                @endif
                            @endif
                        </span>
                    </div>
                    <x-pay :job="$job" class="h-pay" />
                    <x-badge :status="$application->stage" class="h-stage" />
                    <span class="h-date">{{ $date?->format('j M Y') }}</span>
                    <div class="h-actions">
                        @if ($canRate)
                            <a href="{{ route('applications.show', $application) }}#feedback" class="btn btn-outline btn-sm"
                               data-rate="{{ route('reviews.store', $application) }}" data-rate-name="{{ $employerName }}">
                                <x-icon name="star" />Rate
                            </a>
                        @endif
                        <a href="{{ route('applications.show', $application) }}" class="btn btn-soft btn-sm" data-skeleton="detail">Job tracker</a>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{ $applications->links() }}

    {{-- UC20 Give Feedback from History: opened by the Rate button (app.js). Without JavaScript, Rate opens the tracker. --}}
    <dialog class="dialog" data-rate-dialog aria-labelledby="rate-title">
        <form method="POST" action="#" data-rate-form>
            @csrf
            <input type="hidden" name="return" value="history">
            <div class="dlg-body">
                <span class="dlg-icon dlg-icon-amber" aria-hidden="true"><x-icon name="star" /></span>
                <h2 class="dlg-title" id="rate-title" data-rate-title>Rate the employer</h2>
                <p class="dlg-text">Rating is optional. Each of you can rate the other once.</p>
                @include('applications.partials.rate-input', ['idPrefix' => 'dialog-rating'])
                <div class="form-group dlg-field">
                    <label for="dialog-review" class="form-label">Review <span class="form-hint">(optional)</span></label>
                    <textarea id="dialog-review" name="review_text" rows="3" maxlength="1000" class="form-textarea"
                              placeholder="How was it working with them?"></textarea>
                </div>
            </div>
            <div class="dlg-actions">
                <button type="button" class="btn btn-outline" data-rate-cancel>Cancel</button>
                <button type="submit" class="btn btn-primary" data-rate-submit disabled>Submit feedback</button>
            </div>
        </form>
    </dialog>
@endsection
