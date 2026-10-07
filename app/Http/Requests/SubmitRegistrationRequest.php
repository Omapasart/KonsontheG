<?php

namespace App\Http\Requests;

use App\Support\RegistrationWizard;
use Illuminate\Foundation\Http\FormRequest;

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
