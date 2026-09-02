<?php

namespace App\Http\Requests;

use App\Enums\StudentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'student_code' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('students', 'student_code'),
            ],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'batch' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:50'],
            'class_time' => ['nullable', 'string', 'max:100'],
            'monthly_fee' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'status' => ['required', Rule::enum(StudentStatus::class)],
            'enrolled_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
