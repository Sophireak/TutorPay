<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Payment history') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <form method="GET" action="{{ route('payments.index') }}" class="flex flex-wrap items-end gap-3 bg-white shadow-sm rounded-lg border border-gray-100 p-4">
                <div>
                    <x-input-label for="student_id" :value="__('Student')" />
                    <select id="student_id" name="student_id" class="mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All students</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" @selected($filters['student_id'] == $student->id)>{{ $student->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="from" :value="__('From')" />
                    <x-text-input id="from" name="from" type="date" class="mt-1" :value="$filters['from']" />
                </div>

                <div>
                    <x-input-label for="to" :value="__('To')" />
                    <x-text-input id="to" name="to" type="date" class="mt-1" :value="$filters['to']" />
                </div>

                <x-primary-button>{{ __('Filter') }}</x-primary-button>

                <div class="ms-auto text-right">
                    <div class="text-xs uppercase tracking-wide text-gray-500">Total</div>
                    <x-money :amount="$total" class="text-lg font-semibold text-emerald-600" />
                </div>
            </form>

            <div class="bg-white shadow-sm rounded-lg border border-gray-100 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Student</th>
                            <th class="px-5 py-3">For month</th>
                            <th class="px-5 py-3">Method</th>
                            <th class="px-5 py-3">Reference</th>
                            <th class="px-5 py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($payments as $payment)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3">{{ $payment->paid_on->format('d M Y') }}</td>
                                <td class="px-5 py-3">
                                    <a href="{{ route('students.show', $payment->student) }}" class="font-medium text-gray-800 hover:text-indigo-600">{{ $payment->student->name }}</a>
                                </td>
                                <td class="px-5 py-3">{{ $payment->fee->periodLabel() }}</td>
                                <td class="px-5 py-3">{{ $payment->method->label() }}</td>
                                <td class="px-5 py-3 text-gray-500">{{ $payment->reference ?? '—' }}</td>
                                <td class="px-5 py-3 text-right font-semibold text-emerald-600"><x-money :amount="$payment->amount" /></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-gray-500">No payments match these filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $payments->links() }}
        </div>
    </div>
</x-app-layout>
