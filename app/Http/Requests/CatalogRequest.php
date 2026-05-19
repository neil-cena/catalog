<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer' => ['required', 'string'],
            'search'   => ['nullable', 'string'],
            'category' => ['nullable', 'string'],
            'page'     => ['nullable', 'integer', 'min:1'],
        ];
    }
}
