{{--
    One comment and its replies. Replies indent for three levels; deeper ones line up
    and say who they answer, so the thread stays readable on a phone.
    Needs $comment, $depth, plus $byParent, $canComment and $replyingTo from the thread.
--}}
@php
    $author = $comment->author;
    $isMine = $comment->user_id === auth()->id();
    $replies = $byParent->get($comment->id, collect());
    $replyOpen = $replyingTo === $comment->id;
    $whoFor = fn ($item) => $item->user_id === auth()->id() ? 'your comment' : $item->author->displayName();
    $parent = $comment->parent_id ? $application->comments->firstWhere('id', $comment->parent_id) : null;
@endphp

<div @class(['c-item', 'is-mine' => $isMine]) id="comment-{{ $comment->id }}">
    <div class="c-main">
        <x-avatar :user="$author" class="c-avatar" />
        <div class="c-col">
            <div class="c-head">
                <b>{{ $isMine ? 'You' : $author->displayName() }}</b>
                <span>{{ $author->role->label() }}</span>
                <span class="c-time">{{ $comment->created_at->format('j M Y, g:i A') }}</span>
                @if ($comment->submission_id && $depth === 0)
                    <span class="c-tag">{{ $words->item($submissionNumbers[$comment->submission_id] ?? '') }}</span>
                @endif
            </div>

            @if ($depth >= 4 && $parent)
                <span class="c-replyto"><x-icon name="reply" />Replying to {{ $whoFor($parent) }}</span>
            @endif

            <p class="c-body text-prewrap">{{ $comment->body }}</p>

            @if ($canComment)
                <details class="reply-details" data-reply @if ($replyOpen) open @endif>
                    <summary class="c-reply-btn" aria-label="Reply to {{ $whoFor($comment) }}"><x-icon name="reply" />Reply</summary>
                    <form method="POST" action="{{ route('comments.store', $application) }}" class="reply-box">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                        <label for="reply-{{ $comment->id }}" class="reply-to"><x-icon name="reply" />Replying to {{ $whoFor($comment) }}</label>
                        <textarea id="reply-{{ $comment->id }}" name="body" rows="2" maxlength="2000" required
                                  class="form-textarea @if ($replyOpen && $errors->has('body')) is-invalid @endif"
                                  placeholder="Write a reply…">{{ $replyOpen ? old('body') : '' }}</textarea>
                        @if ($replyOpen)
                            @error('body')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        @endif
                        <div class="reply-actions">
                            <span class="reply-hint">Ctrl + Enter to send</span>
                            <button type="button" class="btn btn-outline btn-sm" data-reply-cancel>Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm">Reply</button>
                        </div>
                    </form>
                </details>
            @endif
        </div>
    </div>

    @if ($replies->isNotEmpty())
        <div class="c-replies">
            @foreach ($replies as $reply)
                @include('applications.partials.comment', ['comment' => $reply, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</div>
