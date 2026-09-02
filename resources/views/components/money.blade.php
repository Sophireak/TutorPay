@props(['amount' => 0])

<span {{ $attributes }}>{{ config('tutorpay.currency_symbol') }}{{ number_format((float) $amount, 2) }}</span>
