<?php

namespace App\Http\Requests;

use App\Enums\JobCategory;
use App\Enums\PayType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Shared rules for posting (UC09) and editing (UC11) a job.
 * Start date cannot be in the past, end date cannot be before the start,
 * and the job can last at most gigs.max_job_days days.
 */
abstract class JobRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:5000'],
            'category' => ['required', Rule::enum(JobCategory::class)],
            'location_id' => ['required', 'integer', Rule::exists('locations', 'id')],
            'pay_rate' => ['required', 'numeric', 'min:1', 'max:999999.99'],
            'pay_type' => ['required', Rule::enum(PayType::class)],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['start_date', 'end_date'])) {
                    return;
                }

                $max = (int) config('gigs.max_job_days');
                $days = (int) Carbon::parse($this->input('start_date'))
                    ->diffInDays(Carbon::parse($this->input('end_date'))) + 1;

                if ($days > $max) {
                    $validator->errors()->add('end_date', "A job can last at most {$max} days.");
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.after_or_equal' => 'The start date cannot be in the past.',
            'end_date.after_or_equal' => 'The end date cannot be before the start date.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'location_id' => 'location',
        ];
    }
}
