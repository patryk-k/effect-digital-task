<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

        // needs to be stored because Fpdi works only with files
        $storage = Storage::disk('local');

        $fileName = Str::random() . '.pdf';
        $storage->put($fileName, $decoded);

        try {
            $pdf = new Fpdi();
            $pdf->AddPage();
            $pdf->setSourceFile($storage->path($fileName));
        } catch (Throwable $e) {
            $storage->delete($fileName);
            $fail($this->failureText);
            return;
        }

        $storage->delete($fileName);
    }
}
