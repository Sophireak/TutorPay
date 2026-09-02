<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Models\Fee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePaymentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fee_id' => ['required', 'integer', 'exists:fees,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'paid_on' => ['required', 'date'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Reject payments larger than the amount still owed for the month.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $fee = $this->fee();

                if (! $fee || $validator->errors()->isNotEmpty()) {
                    return;
                }

                if ((float) $this->input('amount') > $fee->outstanding()) {
                    $validator->errors()->add(
                        'amount',
                        sprintf('The payment exceeds the outstanding balance of %s for this month.', number_format($fee->outstanding(), 2))
                    );
                }
            },
        ];
    }

    public function fee(): ?Fee
    {
        return Fee::with('student')->find($this->input('fee_id'));
    }
}
