<?php

namespace App\Http\Requests\Admin;

use App\Models\Registration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('contact_number')) {
            $this->merge([
                'contact_number' => preg_replace('/[\s\-\(\)]/', '', (string) $this->input('contact_number')),
            ]);
        }

        foreach (['last_name', 'first_name', 'middle_initial', 'address', 'email'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $value = trim(strip_tags($this->input($field)));
                if ($field === 'email') {
                    $value = strtolower($value);
                }
                $this->merge([$field => $value]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $photoMax = (int) config('tournament.photo_max_kb', 2048);
        $registration = $this->route('registration');

        return [
            'last_name' => ['required', 'string', 'max:80'],
            'first_name' => ['required', 'string', 'max:80'],
            'middle_initial' => ['nullable', 'string', 'max:5', 'regex:/^[A-Za-z.]+$/'],
            'contact_number' => ['required', 'string', 'regex:/^(09|\+639|639)\d{9}$/'],
            'address' => ['required', 'string', 'max:500'],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                Rule::unique('registrations', 'email')->ignore($registration),
            ],
            'photo' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png',
                'mimetypes:image/jpeg,image/png',
                'max:'.$photoMax,
                'dimensions:max_width=5000,max_height=5000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contact_number.regex' => 'Enter a valid Philippine mobile number (e.g. 09XXXXXXXXX or +639XXXXXXXXX).',
            'photo.mimes' => 'The photo must be a JPG, JPEG, or PNG file.',
            'photo.max' => 'The photo may not be larger than :max kilobytes.',
            'middle_initial.regex' => 'Middle initial may only contain letters.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => Registration::EMAIL_TAKEN_MESSAGE,
        ];
    }
}
