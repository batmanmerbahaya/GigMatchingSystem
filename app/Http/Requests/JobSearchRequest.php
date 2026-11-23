<?php

namespace App\Http\Requests;

use App\Enums\JobCategory;
use App\Enums\PayType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UC01 Search Job: keyword plus faceted filters and a sort order.
 * Every parameter is optional but strictly typed.
 */
class JobSearchRequest extends FormRequest
{
    public const SORTS = [
        'newest' => 'Newest first',
        'pay_high' => 'Highest pay',
        'pay_low' => 'Lowest pay',
        'earliest' => 'Earliest job date',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', Rule::enum(JobCategory::class)],
            'pay_type' => ['nullable', Rule::enum(PayType::class)],
            'state' => ['nullable', 'string', Rule::exists('locations', 'state')],
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')],
            'pay_min' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'pay_max' => ['nullable', 'numeric', 'min:0', 'max:999999.99', 'gte:pay_min'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'sort' => ['nullable', Rule::in(array_keys(self::SORTS))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'q' => 'keyword',
            'location_id' => 'district',
            'pay_type' => 'pay type',
            'pay_min' => 'minimum pay',
            'pay_max' => 'maximum pay',
            'date_from' => 'from date',
            'date_to' => 'to date',
        ];
    }
}
