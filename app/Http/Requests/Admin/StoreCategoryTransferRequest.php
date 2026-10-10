<?php

namespace App\Http\Requests\Admin;

use App\Enums\EntryLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $registration = $this->route('registration');
        $allowed = collect($registration?->entry_level?->higherLevels() ?? [])
            ->map(fn (EntryLevel $level) => $level->value)
            ->all();

        return [
            'requested_category' => ['required', Rule::in($allowed)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'requested_category.required' => 'Please select a higher category.',
            'requested_category.in' => 'Category transfers may only move an applicant to a higher category.',
        ];
    }
}
