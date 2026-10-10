<?php

namespace App\Http\Requests;

use App\Models\Registration;
use App\Support\RegistrationWizard;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator;

class SubmitRegistrationRequest extends FormRequest
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
        $proofMax = (int) config('tournament.proof_max_kb', 5120);
        $proofRequired = RegistrationWizard::ownsTempPath(RegistrationWizard::data()['payment_proof_path'] ?? null)
            ? 'nullable'
            : 'required';

        return [
            'payment_proof' => [
                $proofRequired,
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'mimetypes:image/jpeg,image/png,application/pdf',
                'max:'.$proofMax,
            ],
            'submission_token' => ['required', 'string', 'size:64'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $email = Registration::normalizeEmail(RegistrationWizard::data()['email'] ?? null);

            if ($email !== '' && Registration::emailIsRegistered($email)) {
                $validator->errors()->add('email', Registration::EMAIL_TAKEN_MESSAGE);
            }

            $level = RegistrationWizard::data()['entry_level'] ?? null;

            if (is_string($level) && $level !== '') {
                $capacity = app(\App\Services\CategoryCapacityService::class);

                if ($capacity->statusFor($level)['is_full']) {
                    $validator->errors()->add('entry_level', $capacity->fullMessage($level));
                }
            }
        });
    }

    protected function failedValidation(ValidatorContract $validator): void
    {
        if ($validator->errors()->has('email')) {
            throw new HttpResponseException(redirect()->route('register.email-taken'));
        }

        if ($validator->errors()->has('entry_level')) {
            throw new HttpResponseException(redirect()->route('register.category-full', [
                'level' => RegistrationWizard::data()['entry_level'] ?? 'beginner',
            ]));
        }

        parent::failedValidation($validator);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_proof.required' => 'Please upload your proof of payment.',
            'payment_proof.mimes' => 'Proof of payment must be a JPG, JPEG, PNG, or PDF file.',
            'payment_proof.max' => 'Proof of payment may not be larger than :max kilobytes.',
        ];
    }
}
