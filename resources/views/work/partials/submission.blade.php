@php
    use App\Enums\ApprovalMethod;
    use App\Enums\SubmissionStatus;
@endphp

<article class="submission-card">
    <header class="submission-header">
        <h3 class="submission-title">{{ $words->item($number) }}</h3>
        <x-badge :status="$submission->status" :label="$words->status($submission->status)" />
        <span class="text-muted text-small">{{ $submission->submitted_at->format('j M Y, g:i A') }}</span>
    </header>

    <p class="detail-text text-prewrap">{{ $submission->note }}</p>

    @if ($submission->files->isNotEmpty())
        <ul class="file-list">
            @foreach ($submission->files as $file)
                <li>
                    <a href="{{ route('files.download', $file) }}" class="file-link">{{ $file->original_name }}</a>
                    <span class="text-muted text-small">{{ $file->sizeLabel() }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($submission->status === SubmissionStatus::Approved && $application->approval_method === ApprovalMethod::Automatic)
        <p class="text-muted text-small">{{ ucfirst($words->approvedWord()) }} automatically after {{ config('gigs.auto_approve_days') }} days without a response.</p>
    @endif

    {{-- UC14 Approve Submission: approve (confirm) or request changes (send back). --}}
    @can('respond', $submission)
        <div class="submission-actions">
            <form method="POST" action="{{ route('employer.submissions.approve', $submission) }}"
                  data-confirm="{{ $words->approveQuestion() }}"
                  data-confirm-ok="{{ $words->approveYes() }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-primary btn-sm">{{ $words->approveButton() }}</button>
            </form>

            <details class="inline-details" @error('comment') open @enderror>
                <summary class="btn btn-outline btn-sm">{{ $words->sendBackButton() }}</summary>
                <form method="POST" action="{{ route('employer.submissions.request-changes', $submission) }}">
                    @csrf
                    @method('PATCH')
                    <div class="form-group">
                        <label for="comment-{{ $submission->id }}" class="form-label">{{ $words->sendBackQuestion() }}</label>
                        <textarea id="comment-{{ $submission->id }}" name="comment" rows="3" maxlength="2000" required
                                  class="form-textarea @error('comment') is-invalid @enderror">{{ old('comment') }}</textarea>
                        @error('comment')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-danger btn-sm">{{ $words->sendBackSubmit() }}</button>
                </form>
            </details>
        </div>
        <p class="text-muted text-small">
            If you do not respond by {{ $submission->submitted_at->copy()->addDays((int) config('gigs.auto_approve_days'))->format('j M Y, g:i A') }},
            this {{ $words->choose('submission', 'report') }} is {{ $words->approvedWord() }} automatically.
        </p>
    @endcan
</article>
