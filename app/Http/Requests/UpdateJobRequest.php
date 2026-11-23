<?php

namespace App\Http\Requests;

class UpdateJobRequest extends JobRequest
{
    /**
     * Owner only, and only until the first application (JobPolicy::update).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('job')) ?? false;
    }
}
