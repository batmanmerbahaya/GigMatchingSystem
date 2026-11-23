{{-- UC20 Give Feedback: optional, after payment is Confirmed, one rating per party. --}}
<section class="panel" id="feedback">
    <div class="panel-header">
        <div>
            <h2 class="panel-title">Feedback</h2>
            <p class="panel-subtitle">Rating is optional. Each of you can rate the other once.</p>
        </div>
    </div>
    <div class="panel-body stack">
        @if ($myReview)
            <div class="review-card">
                <p class="review-heading">Your rating of {{ $otherParty->displayName() }}</p>
                <x-stars :rating="$myReview->rating" />
                @if (filled($myReview->review_text))
                    <p class="detail-text text-prewrap">{{ $myReview->review_text }}</p>
                @endif
            </div>
        @else
            @can('review', $application)
                <form method="POST" action="{{ route('reviews.store', $application) }}">
                    @csrf
                    <div class="form-group">
                        <span class="form-label">Rate {{ $otherParty->displayName() }}</span>
                        @include('applications.partials.rate-input', ['idPrefix' => 'rating'])
                        @error('rating')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="review_text" class="form-label">Review <span class="form-hint">(optional)</span></label>
                        <textarea id="review_text" name="review_text" rows="3" maxlength="1000"
                                  class="form-textarea @error('review_text') is-invalid @enderror">{{ old('review_text') }}</textarea>
                        @error('review_text')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Submit feedback</button>
                </form>
            @endcan
        @endif

        @if ($theirReview)
            <div class="review-card">
                <p class="review-heading">{{ $otherParty->displayName() }} rated you</p>
                <x-stars :rating="$theirReview->rating" />
                @if (filled($theirReview->review_text))
                    <p class="detail-text text-prewrap">{{ $theirReview->review_text }}</p>
                @endif
            </div>
        @else
            <p class="text-muted text-small">{{ $otherParty->displayName() }} has not rated you yet.</p>
        @endif
    </div>
</section>
