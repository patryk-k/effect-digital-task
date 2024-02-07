<?php

namespace App\Http\Requests;

use App\Rules\ValidBase64Pdf;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document' => [
                'required',
                new ValidBase64Pdf()
            ]
        ];
    }
}
