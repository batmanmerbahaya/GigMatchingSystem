{{--
    Comment thread (spec section 6): opens at Accepted, read-only from Confirmed.
    Replies sit under the comment they answer, indented. New comments go at the bottom in time order.
--}}
@php
    $byParent = $application->comments->groupBy(fn ($comment) => $comment->parent_id ?? 0);
    $canComment = auth()->user()->can('comment', $application);
    $commentCount = $application->comments->count();
    $replyingTo = (int) old('parent_id');
@endphp

<section class="panel" id="thread">
    <div class="panel-header">
        <div>
            <h2 class="panel-title">Comments</h2>
            <p class="panel-subtitle">Reply to a comment to keep that conversation together. Comments cannot be edited or deleted.</p>
        </div>
        <span class="count-chip">{{ $commentCount }} {{ Str::plural('comment', $commentCount) }}</span>
    </div>
    <div class="panel-body">
        <div class="thread-list">
            @forelse ($byParent->get(0, collect()) as $comment)
                @include('applications.partials.comment', ['comment' => $comment, 'depth' => 0])
            @empty
                <p class="text-muted">No comments yet.</p>
            @endforelse
        </div>

        {{-- UC19 Post Comment --}}
        @if ($canComment)
            <form method="POST" action="{{ route('comments.store', $application) }}" class="composer">
                @csrf
                <label for="body" class="form-label">New comment</label>
                <textarea id="body" name="body" rows="3" maxlength="2000" required
                          class="form-textarea @if (! $replyingTo && $errors->has('body')) is-invalid @endif"
                          placeholder="Write a comment for {{ $otherParty->displayName() }}…">{{ $replyingTo ? '' : old('body') }}</textarea>
                @unless ($replyingTo)
                    @error('body')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                @endunless
                <div class="composer-row">
                    @if ($application->submissions->isNotEmpty())
                        <div class="form-group composer-about">
                            <label for="submission_id" class="form-label">About</label>
                            <select id="submission_id" name="submission_id" class="form-select">
                                <option value="">The job in general</option>
                                @foreach ($application->submissions as $submission)
                                    <option value="{{ $submission->id }}" @selected((int) old('submission_id') === $submission->id)>
                                        {{ $words->item($submissionNumbers[$submission->id]) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <span class="reply-hint">Ctrl + Enter to send</span>
                    <button type="submit" class="btn btn-primary">Post comment</button>
                </div>
            </form>
        @else
            <p class="text-muted text-small">The thread is read-only because payment has been confirmed.</p>
        @endif
    </div>
</section>
