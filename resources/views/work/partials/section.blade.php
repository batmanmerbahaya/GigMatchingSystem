{{--
    Work submission and approval (spec section 5): UC06 and UC14.
    $words picks the wording: "Submit work" for online jobs, "Mark the job as done" for in-person jobs.
--}}
@php
    $limits = config('gigs.submissions');
@endphp

<section class="panel" id="work">
    <div class="panel-header">
        <div>
            <h2 class="panel-title">{{ $words->sectionTitle() }}</h2>
            <p class="panel-subtitle">{{ $words->sectionSubtitle() }}</p>
        </div>
    </div>
    <div class="panel-body stack">

        @can('submitWork', $application)
            <form method="POST" action="{{ route('worker.submissions.store', $application) }}"
                  enctype="multipart/form-data" class="submit-work-form">
                @csrf
                <h3 class="detail-section-title">{{ $words->formTitle() }}</h3>
                <div class="form-group">
                    <label for="note" class="form-label">{{ $words->noteLabel() }}</label>
                    <textarea id="note" name="note" rows="3" maxlength="2000" required
                              class="form-textarea @error('note') is-invalid @enderror"
                              placeholder="{{ $words->notePlaceholder() }}">{{ old('note') }}</textarea>
                    @error('note')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="files" class="form-label">{{ $words->filesLabel() }} <span class="form-hint">(optional)</span></label>
                    <input id="files" name="files[]" type="file" multiple class="form-input"
                           accept=".{{ implode(',.', $limits['extensions']) }}" data-max-files="{{ $limits['max_files'] }}">
                    <p class="form-hint">
                        {{ $words->choose('Up to', 'Proof such as a photo of the finished work. Up to') }}
                        {{ $limits['max_files'] }} files, {{ $limits['max_file_kb'] / 1024 }} MB each. PDF, Word, image or ZIP.
                    </p>
                    @error('files')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                    @foreach ($errors->get('files.*') as $messages)
                        @foreach ($messages as $message)
                            <p class="form-error">{{ $message }}</p>
                        @endforeach
                    @endforeach
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">{{ $words->submitButton() }}</button>
                </div>
            </form>
        @elseif ($isWorker && $application->isAt(\App\Enums\ApplicationStage::Accepted) && ! $application->hasApprovedWork())
            <p class="text-muted text-small">{{ $words->waitingNote() }}</p>
        @endcan

        @forelse ($application->submissions as $submission)
            @include('work.partials.submission', ['number' => $submissionNumbers[$submission->id]])
        @empty
            <p class="text-muted">{{ $words->nothingYet() }}</p>
        @endforelse
    </div>
</section>
