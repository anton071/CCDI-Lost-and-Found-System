<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\ItemReport::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', Rule::exists('categories', 'id')->where('is_active', true)],
            'item_name' => ['required', 'string', 'min:3', 'max:180'],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
            'brand' => ['nullable', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'max:100'],
            'identifying_features' => ['nullable', 'string', 'max:2000'],
            'location_description' => ['required', 'string', 'max:255'],
            'incident_date' => ['required', 'date', 'before_or_equal:today'],
            'incident_time' => ['nullable', 'date_format:H:i'],
            'photo' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:'.config('ccdi.uploads.max_kb'), 'dimensions:min_width=100,min_height=100'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.max' => 'The photograph must not be larger than '.(config('ccdi.uploads.max_kb') / 1024).' MB.',
            'photo.uploaded' => 'The photograph could not be uploaded. It may be larger than the server allows (PHP upload_max_filesize / post_max_size) - ask the administrator to raise them to at least '.(config('ccdi.uploads.max_kb') / 1024).' MB.',
            'photo.mimetypes' => 'The photograph must be a JPEG, PNG or WebP image.',
            'photo.dimensions' => 'The photograph must be at least 100 × 100 pixels.',
            'incident_date.before_or_equal' => 'The date cannot be in the future.',
        ];
    }
}
