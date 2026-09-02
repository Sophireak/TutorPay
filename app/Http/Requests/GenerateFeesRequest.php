<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class GenerateFeesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'month' => ['required', 'date_format:Y-m'],
            'due_day' => ['nullable', 'integer', 'min:1', 'max:28'],
        ];
    }

    public function month(): Carbon
    {
        return Carbon::createFromFormat('Y-m', $this->string('month')->value())->startOfMonth();
    }

    public function dueDay(): ?int
    {
        return $this->filled('due_day') ? $this->integer('due_day') : null;
    }
}
