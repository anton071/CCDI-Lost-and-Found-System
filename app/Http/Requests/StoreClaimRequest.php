<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;   // item-level rules are enforced by ItemReportPolicy@claim and ClaimService
    }

    public function rules(): array
    {
        return [
            'claim_message' => ['required', 'string', 'min:20', 'max:2000'],
            'related_lost_report_id' => ['nullable', 'integer', 'exists:item_reports,id'],
        ];
    }

    public function messages(): array
    {
        return ['claim_message.min' => 'Please describe the item in at least 20 characters (unique marks, contents, how you lost it).'];
    }
}
