<?php

namespace App\Http\Requests;

use App\Enums\TournamentExperience;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExperienceRequest extends FormRequest
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
            'has_tournament_experience' => ['required', Rule::enum(TournamentExperience::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'has_tournament_experience.required' => 'Please select whether you have participated in a tournament before.',
        ];
    }
}
