{{-- The one next step available to the viewer at the current stage. --}}
@php
    use App\Enums\ApplicationStage;
@endphp

@can('withdraw', $application)
    <div class="alert alert-info alert-with-action">
        <span>Waiting for the employer to decide. You can withdraw until you are accepted.</span>
        <form method="POST" action="{{ route('worker.applications.withdraw', $application) }}"
              data-confirm="Withdraw this application? You cannot apply for the same job again."
              data-confirm-ok="Yes, withdraw" data-confirm-tone="danger">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-outline btn-sm">Withdraw application</button>
        </form>
    </div>
@endcan

@if (! $isWorker && $application->isAt(ApplicationStage::Applied))
    <div class="alert alert-info alert-with-action">
        @if ($job->isOpen())
            <span>This applicant is waiting for your decision.</span>
            <span class="inline-actions">
                <form method="POST" action="{{ route('employer.applications.accept', $application) }}"
                      data-confirm="Accept {{ $application->worker->name }}? All other applicants will be rejected."
                      data-confirm-ok="Yes, accept">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-primary btn-sm">Accept</button>
                </form>
                <form method="POST" action="{{ route('employer.applications.reject', $application) }}"
                      data-confirm="Reject {{ $application->worker->name }}?"
                      data-confirm-ok="Yes, reject" data-confirm-tone="danger">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-outline btn-sm">Reject</button>
                </form>
            </span>
        @else
            <span>This listing is hidden, so its applicants cannot be accepted. You can close the job.</span>
        @endif
    </div>
@endif

@if ($application->isAt(ApplicationStage::Accepted))
    @can('finish', $application)
        <div class="alert alert-success alert-with-action">
            <span>{{ $words->finishPrompt() }}</span>
            <form method="POST" action="{{ route('worker.applications.finish', $application) }}"
                  data-confirm="Finish this job? The employer will be asked to confirm payment."
                  data-confirm-ok="Yes, finish job">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-primary btn-sm">Finish Job</button>
            </form>
        </div>
    @elseif ($isWorker)
        <p class="text-muted text-small">{{ $words->workerHint() }}</p>
    @else
        <p class="text-muted text-small">{{ $words->employerHint((int) config('gigs.auto_approve_days')) }}</p>
    @endcan
@endif

@if ($application->isAt(ApplicationStage::Completed))
    @can('confirmPayment', $application)
        <div class="alert alert-warning alert-with-action">
            <span>The worker has finished. Pay them directly, then confirm the payment here.</span>
            <form method="POST" action="{{ route('employer.applications.confirm-payment', $application) }}"
                  data-confirm="Confirm that you have paid {{ $application->worker->name }}? The job will be closed."
                  data-confirm-ok="Yes, I have paid">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-primary btn-sm">Confirm payment</button>
            </form>
        </div>
    @else
        <p class="text-muted text-small">You finished the job. Waiting for the employer to confirm payment.</p>
    @endcan
@endif
