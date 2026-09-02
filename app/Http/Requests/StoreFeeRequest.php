<?php

namespace App\Http\Requests;

use App\Models\Fee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFeeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                Rule::exists('students', 'id')->where('user_id', $this->user()->id),
            ],
            'period_month' => ['required', 'date_format:Y-m'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * A student may only be billed once per month.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $exists = Fee::query()
                    ->where('student_id', $this->integer('student_id'))
                    ->whereDate('period_month', $this->periodMonth())
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('period_month', 'This student has already been billed for that month.');
                }
            },
        ];
    }

    /**
     * The first day of the billing month, ready for persistence.
     */
    public function periodMonth(): string
    {
        return $this->date('period_month', 'Y-m')?->startOfMonth()->toDateString() ?? '';
    }
}
