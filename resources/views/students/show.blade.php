<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $student->name }}</h2>
                <p class="text-sm text-gray-500">{{ $student->batch ?? 'No batch' }} &middot; {{ $student->status->label() }}</p>
            </div>
            <a href="{{ route('students.edit', $student) }}" class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-700 border border-gray-300 hover:bg-gray-50">
                {{ __('Edit') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <div class="grid gap-4 sm:grid-cols-3">
                <x-stat-card label="Monthly fee" :value="config('tutorpay.currency_symbol').number_format((float) $student->monthly_fee, 2)" />
                <x-stat-card label="Total paid" tone="positive" :value="config('tutorpay.currency_symbol').number_format($student->totalPaid(), 2)" />
                <x-stat-card label="Outstanding" tone="negative" :value="config('tutorpay.currency_symbol').number_format($student->outstanding(), 2)" />
            </div>

            <div class="bg-white shadow-sm rounded-lg border border-gray-100 p-5 grid gap-3 sm:grid-cols-3 text-sm">
                <div><span class="text-gray-500">Guardian:</span> {{ $student->guardian_name ?? '—' }}</div>
                <div><span class="text-gray-500">Phone:</span> {{ $student->phone ?? '—' }}</div>
                <div><span class="text-gray-500">Email:</span> {{ $student->email ?? '—' }}</div>
                <div><span class="text-gray-500">Enrolled:</span> {{ $student->enrolled_on->format('d M Y') }}</div>
                @if ($student->notes)
                    <div class="sm:col-span-3"><span class="text-gray-500">Notes:</span> {{ $student->notes }}</div>
                @endif
            </div>

            <div class="bg-white shadow-sm rounded-lg border border-gray-100 overflow-x-auto">
                <h3 class="px-5 py-4 font-semibold text-gray-800 border-b border-gray-100">Monthly fees</h3>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Month</th>
                            <th class="px-5 py-3 text-right">Amount</th>
                            <th class="px-5 py-3 text-right">Paid</th>
                            <th class="px-5 py-3 text-right">Outstanding</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($student->fees as $fee)
                            <tr>
                                <td class="px-5 py-3">{{ $fee->periodLabel() }}</td>
                                <td class="px-5 py-3 text-right"><x-money :amount="$fee->amount" /></td>
                                <td class="px-5 py-3 text-right text-emerald-600"><x-money :amount="$fee->paidAmount()" /></td>
                                <td class="px-5 py-3 text-right"><x-money :amount="$fee->outstanding()" /></td>
                                <td class="px-5 py-3"><x-fee-status-badge :status="$fee->status()" /></td>
                                <td class="px-5 py-3 text-right">
                                    @unless ($fee->isSettled())
                                        <a href="{{ route('payments.create', ['fee' => $fee->id]) }}" class="text-indigo-600 hover:text-indigo-800">Record payment</a>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-gray-500">No fees billed yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white shadow-sm rounded-lg border border-gray-100 overflow-x-auto">
                <h3 class="px-5 py-4 font-semibold text-gray-800 border-b border-gray-100">Payment history</h3>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">For month</th>
                            <th class="px-5 py-3">Method</th>
                            <th class="px-5 py-3">Reference</th>
                            <th class="px-5 py-3 text-right">Amount</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($student->payments as $payment)
                            <tr>
                                <td class="px-5 py-3">{{ $payment->paid_on->format('d M Y') }}</td>
                                <td class="px-5 py-3">{{ $payment->fee->periodLabel() }}</td>
                                <td class="px-5 py-3">{{ $payment->method->label() }}</td>
                                <td class="px-5 py-3 text-gray-500">{{ $payment->reference ?? '—' }}</td>
                                <td class="px-5 py-3 text-right font-semibold text-emerald-600"><x-money :amount="$payment->amount" /></td>
                                <td class="px-5 py-3 text-right">
                                    <form method="POST" action="{{ route('payments.destroy', $payment) }}"
                                          onsubmit="return confirm('Delete this payment?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-600 hover:text-rose-800">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-gray-500">No payments recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
