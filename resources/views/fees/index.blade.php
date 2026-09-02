<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Monthly fees') }}</h2>
            <span class="text-sm text-gray-500">{{ $month->format('F Y') }}</span>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ addFee: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <div class="grid gap-4 sm:grid-cols-3">
                <x-stat-card label="Billed" :value="config('tutorpay.currency_symbol').number_format($summary['billed'], 2)" />
                <x-stat-card label="Collected" tone="positive" :value="config('tutorpay.currency_symbol').number_format($summary['collected'], 2)" />
                <x-stat-card label="Outstanding" tone="negative" :value="config('tutorpay.currency_symbol').number_format($summary['outstanding'], 2)" />
            </div>

            <div class="flex flex-wrap items-end gap-3 bg-white shadow-sm rounded-lg border border-gray-100 p-4">
                <form method="GET" action="{{ route('fees.index') }}" class="flex items-end gap-3">
                    <div>
                        <x-input-label for="month" :value="__('Billing month')" />
                        <x-text-input id="month" name="month" type="month" class="mt-1" :value="$month->format('Y-m')" />
                    </div>
                    <x-primary-button>{{ __('Show') }}</x-primary-button>
                </form>

                <form method="POST" action="{{ route('fees.generate') }}" class="flex items-end gap-3">
                    @csrf
                    <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                    <div>
                        <x-input-label for="due_day" :value="__('Due day')" />
                        <x-text-input id="due_day" name="due_day" type="number" min="1" max="28" class="mt-1 w-24"
                                      :value="old('due_day', config('tutorpay.default_due_day'))" />
                    </div>
                    <x-secondary-button type="submit">{{ __('Generate fees for active students') }}</x-secondary-button>
                </form>

                <x-secondary-button type="button" x-on:click="addFee = ! addFee">{{ __('Add single fee') }}</x-secondary-button>
            </div>

            <div x-show="addFee" x-cloak class="bg-white shadow-sm rounded-lg border border-gray-100 p-4">
                <form method="POST" action="{{ route('fees.store') }}" class="grid gap-4 sm:grid-cols-5 items-end">
                    @csrf

                    <div class="sm:col-span-2">
                        <x-input-label for="student_id" :value="__('Student')" />
                        <select id="student_id" name="student_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('student_id')" />
                    </div>

                    <div>
                        <x-input-label for="period_month" :value="__('Month')" />
                        <x-text-input id="period_month" name="period_month" type="month" class="mt-1 block w-full"
                                      :value="old('period_month', $month->format('Y-m'))" />
                        <x-input-error class="mt-2" :messages="$errors->get('period_month')" />
                    </div>

                    <div>
                        <x-input-label for="amount" :value="__('Amount')" />
                        <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('amount')" />
                        <x-input-error class="mt-2" :messages="$errors->get('amount')" />
                    </div>

                    <x-primary-button>{{ __('Add fee') }}</x-primary-button>
                </form>
            </div>

            <div class="bg-white shadow-sm rounded-lg border border-gray-100 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Student</th>
                            <th class="px-5 py-3">Due date</th>
                            <th class="px-5 py-3 text-right">Amount</th>
                            <th class="px-5 py-3 text-right">Paid</th>
                            <th class="px-5 py-3 text-right">Outstanding</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($fees as $fee)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3">
                                    <a href="{{ route('students.show', $fee->student) }}" class="font-medium text-gray-800 hover:text-indigo-600">{{ $fee->student->name }}</a>
                                </td>
                                <td class="px-5 py-3 text-gray-600">{{ $fee->due_date?->format('d M Y') ?? '—' }}</td>
                                <td class="px-5 py-3 text-right"><x-money :amount="$fee->amount" /></td>
                                <td class="px-5 py-3 text-right text-emerald-600"><x-money :amount="$fee->paidAmount()" /></td>
                                <td class="px-5 py-3 text-right"><x-money :amount="$fee->outstanding()" /></td>
                                <td class="px-5 py-3"><x-fee-status-badge :status="$fee->status()" /></td>
                                <td class="px-5 py-3 text-right space-x-3 whitespace-nowrap">
                                    @unless ($fee->isSettled())
                                        <a href="{{ route('payments.create', ['fee' => $fee->id]) }}" class="text-indigo-600 hover:text-indigo-800">Record payment</a>
                                    @endunless
                                    @if ($fee->paidAmount() <= 0)
                                        <form method="POST" action="{{ route('fees.destroy', $fee) }}" class="inline"
                                              onsubmit="return confirm('Remove this fee?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:text-rose-800">Remove</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-8 text-center text-gray-500">
                                    No fees for {{ $month->format('F Y') }} yet. Generate them for your active students.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
