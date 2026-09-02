<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Monthly collection') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-stat-card label="Total outstanding (all months)" tone="negative"
                         :value="config('tutorpay.currency_symbol').number_format($totalOutstanding, 2)" />

            <div class="bg-white shadow-sm rounded-lg border border-gray-100 overflow-x-auto">
                <h3 class="px-5 py-4 font-semibold text-gray-800 border-b border-gray-100">Last 6 months</h3>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Month</th>
                            <th class="px-5 py-3 text-right">Billed</th>
                            <th class="px-5 py-3 text-right">Collected</th>
                            <th class="px-5 py-3 text-right">Outstanding</th>
                            <th class="px-5 py-3 text-right">Collection rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($trend as $row)
                            <tr class="{{ $row['month']->equalTo($currentMonth) ? 'bg-indigo-50/50' : '' }}">
                                <td class="px-5 py-3">
                                    <a href="{{ route('fees.index', ['month' => $row['month']->format('Y-m')]) }}" class="text-gray-800 hover:text-indigo-600">
                                        {{ $row['month']->format('F Y') }}
                                    </a>
                                </td>
                                <td class="px-5 py-3 text-right"><x-money :amount="$row['billed']" /></td>
                                <td class="px-5 py-3 text-right text-emerald-600"><x-money :amount="$row['collected']" /></td>
                                <td class="px-5 py-3 text-right text-rose-600"><x-money :amount="$row['outstanding']" /></td>
                                <td class="px-5 py-3 text-right">
                                    {{ $row['billed'] > 0 ? number_format($row['collected'] / $row['billed'] * 100, 1) : '0.0' }}%
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="bg-white shadow-sm rounded-lg border border-gray-100 overflow-x-auto">
                <h3 class="px-5 py-4 font-semibold text-gray-800 border-b border-gray-100">Outstanding by student</h3>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Student</th>
                            <th class="px-5 py-3 text-right">Billed</th>
                            <th class="px-5 py-3 text-right">Paid</th>
                            <th class="px-5 py-3 text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($studentsWithBalance as $student)
                            <tr>
                                <td class="px-5 py-3">
                                    <a href="{{ route('students.show', $student) }}" class="text-gray-800 hover:text-indigo-600">{{ $student->name }}</a>
                                </td>
                                <td class="px-5 py-3 text-right"><x-money :amount="$student->billed_total ?? 0" /></td>
                                <td class="px-5 py-3 text-right text-emerald-600"><x-money :amount="$student->paid_total ?? 0" /></td>
                                <td class="px-5 py-3 text-right font-semibold text-rose-600"><x-money :amount="$student->balance" /></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-gray-500">Everyone is paid up.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
