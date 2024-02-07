<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use setasign\Fpdi\Fpdi;
use Throwable;

class ValidBase64Pdf implements ValidationRule
{
    private $failureText = 'The document provided is not a valid pdf file.';

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if(!is_string($value)) {
            $fail($this->failureText);
            return;
        }

        $decoded = base64_decode($value, true);

        if($decoded === false) {
            $fail($this->failureText);
            return;
        }

        try {
            $pdf = new Fpdi();
            $pdf->AddPage();
            $pdf->setSourceFile($decoded);
        } catch (Throwable $e) {
            $fail($this->failureText);
            return;
        }
    }
}
