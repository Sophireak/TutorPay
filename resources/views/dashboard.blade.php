<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Dashboard') }}</h2>
            <span class="text-sm text-gray-500">{{ $month->format('F Y') }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-stat-card
                    label="Billed this month"
                    :value="config('tutorpay.currency_symbol').number_format($summary['billed'], 2)"
                    :hint="$summary['students_billed'].' student(s) billed'" />

                <x-stat-card
                    label="Collected this month"
                    tone="positive"
                    :value="config('tutorpay.currency_symbol').number_format($summary['collected'], 2)"
                    :hint="$summary['fully_paid'].' fully paid'" />

                <x-stat-card
                    label="Outstanding this month"
                    tone="negative"
                    :value="config('tutorpay.currency_symbol').number_format($summary['outstanding'], 2)"
                    :hint="'Cash received: '.config('tutorpay.currency_symbol').number_format($cashReceived, 2)" />

                <x-stat-card
                    label="Total outstanding"
                    tone="negative"
                    :value="config('tutorpay.currency_symbol').number_format($totalOutstanding, 2)"
                    :hint="$activeStudents.' active student(s)'" />
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="bg-white shadow-sm rounded-lg border border-gray-100">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <h3 class="font-semibold text-gray-800">Students with a balance</h3>
                        <a href="{{ route('reports.monthly') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Reports</a>
                    </div>

                    @forelse ($studentsWithBalance as $student)
                        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-50 last:border-0">
                            <a href="{{ route('students.show', $student) }}" class="text-sm font-medium text-gray-800 hover:text-indigo-600">
                                {{ $student->name }}
                            </a>
                            <x-money :amount="$student->balance" class="text-sm font-semibold text-rose-600" />
                        </div>
                    @empty
                        <p class="px-5 py-6 text-sm text-gray-500">Nothing outstanding. Everyone is paid up.</p>
                    @endforelse
                </div>

                <div class="bg-white shadow-sm rounded-lg border border-gray-100">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <h3 class="font-semibold text-gray-800">Recent payments</h3>
                        <a href="{{ route('payments.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">All payments</a>
                    </div>

                    @forelse ($recentPayments as $payment)
                        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-50 last:border-0">
                            <div>
                                <a href="{{ route('students.show', $payment->student) }}" class="text-sm font-medium text-gray-800 hover:text-indigo-600">
                                    {{ $payment->student->name }}
                                </a>
                                <div class="text-xs text-gray-500">
                                    {{ $payment->paid_on->format('d M Y') }} &middot; {{ $payment->fee->periodLabel() }}
                                </div>
                            </div>
                            <x-money :amount="$payment->amount" class="text-sm font-semibold text-emerald-600" />
                        </div>
                    @empty
                        <p class="px-5 py-6 text-sm text-gray-500">No payments recorded yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('students.create') }}" class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                    Add student
                </a>
                <a href="{{ route('fees.index', ['month' => $month->format('Y-m')]) }}" class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-700 border border-gray-300 hover:bg-gray-50">
                    Monthly fees
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
