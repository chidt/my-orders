<?php

namespace App\Concerns;

use App\Models\Province;
use App\Models\Ward;
use Closure;
use Illuminate\Validation\Rule;

trait AddressValidationRules
{
    /**
     * Get validation rules used to validate address fields.
     *
     * @param  array  $input  The input data being validated
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function addressRules(array $input = []): array
    {
        return [
            'address' => ['required', 'string', 'max:255'],
            'province_id' => [
                'required',
                'integer',
                Rule::exists(Province::class, 'id'),
            ],
            'ward_id' => [
                'required',
                'integer',
                Rule::exists(Ward::class, 'id'),
                function (string $attribute, mixed $value, Closure $fail) {
                    // Get the request data from the validation context
                    $request = request();
                    $provinceId = $request->input('province_id');

                    if (! $provinceId) {
                        return;
                    }

                    $ward = Ward::find($value);
                    if (! $ward || $ward->province_id != $provinceId) {
                        $fail('The selected ward does not belong to the selected province.');
                    }
                },
            ],
        ];
    }
}
