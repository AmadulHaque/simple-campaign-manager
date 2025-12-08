<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
            'scheduled_at' => 'nullable|date|after:now',
        ];
    }

    /**
     * Get the custom error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The campaign name is required.',
            'subject.required' => 'The email subject is required.',
            'body.required' => 'The email body is required.',
            'contact_ids.required' => 'Please select at least one contact.',
            'contact_ids.*.exists' => 'One or more selected contacts are invalid.',
            'scheduled_at.after' => 'The scheduled time must be in the future.',
        ];
    }
}
