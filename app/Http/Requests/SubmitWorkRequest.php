<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UC06 Submit Work: a note plus up to 5 files of 10 MB each, of type
 * PDF, Word, image or ZIP. Files are optional because some gigs, such
 * as cleaning or delivery, have nothing to upload.
 */
class SubmitWorkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('submitWork', $this->route('application')) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $limits = config('gigs.submissions');

        return [
            'note' => ['required', 'string', 'max:2000'],
            'files' => ['nullable', 'array', 'max:'.$limits['max_files']],
            'files.*' => ['file', 'max:'.$limits['max_file_kb'], 'mimes:'.implode(',', $limits['extensions'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $limits = config('gigs.submissions');

        return [
            'files.max' => 'You can upload at most '.$limits['max_files'].' files per submission.',
            'files.*.max' => 'Each file must be '.($limits['max_file_kb'] / 1024).' MB or smaller.',
            'files.*.mimes' => 'Files must be PDF, Word, image or ZIP.',
            'files.*.file' => 'One of the files failed to upload. It may be larger than the server allows.',
        ];
    }
}
