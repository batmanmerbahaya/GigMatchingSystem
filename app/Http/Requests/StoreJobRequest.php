<?php

namespace App\Http\Requests;

use App\Models\Job;

class StoreJobRequest extends JobRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Job::class) ?? false;
    }
}
