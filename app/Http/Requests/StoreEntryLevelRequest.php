<?php

namespace App\Http\Requests;

use App\Enums\EntryLevel;
use App\Services\CategoryCapacityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEntryLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entry_level' => ['required', Rule::enum(EntryLevel::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $level = $this->input('entry_level');

            if (! is_string($level) || $level === '' || $validator->errors()->has('entry_level')) {
                return;
            }

            $capacity = app(CategoryCapacityService::class);

            if ($capacity->statusFor($level)['is_full']) {
                $validator->errors()->add('entry_level', $capacity->fullMessage($level));
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'entry_level.required' => 'Please select your entry level.',
        ];
    }
}
