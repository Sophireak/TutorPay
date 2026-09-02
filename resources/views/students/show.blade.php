<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-3 min-w-0">
                <a href="{{ route('students.index') }}"
                   class="mt-0.5 shrink-0 text-gray-400 hover:text-gray-600 transition">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight truncate">{{ $student->name }}</h2>
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium
                            {{ $student->isActive() ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-500' }}">
                            {{ $student->status->label() }}
                        </span>
                    </div>
                    <div class="mt-0.5 flex items-center gap-3 text-sm text-gray-500 flex-wrap">
                        <span class="font-mono text-xs">{{ $student->student_code }}</span>
                        @if ($student->grade)
                            <span>&middot; Grade {{ $student->grade }}</span>
                        @endif
                        @if ($student->class_time)
                            <span>&middot; {{ $student->class_time }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <a href="{{ route('students.edit', $student) }}"
               class="shrink-0 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                {{ __('Edit') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            {{-- Summary stats --}}
            <div class="grid gap-3 grid-cols-2 sm:grid-cols-4">
                <x-stat-card label="Monthly fee"
                             :value="config('tutorpay.currency_symbol').number_format((float) $student->monthly_fee, 2)" />
                <x-stat-card label="Total billed"
                             :value="config('tutorpay.currency_symbol').number_format($student->totalBilled(), 2)" />
                <x-stat-card label="Total paid" tone="positive"
                             :value="config('tutorpay.currency_symbol').number_format($student->totalPaid(), 2)" />
                <x-stat-card label="Outstanding" tone="{{ $student->outstanding() > 0 ? 'negative' : 'default' }}"
                             :value="config('tutorpay.currency_symbol').number_format($student->outstanding(), 2)" />
            </div>

            {{-- Student details card --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Student details</h3>
                <dl class="grid gap-x-6 gap-y-3 grid-cols-2 sm:grid-cols-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Student code</dt>
                        <dd class="font-mono font-medium text-gray-900">{{ $student->student_code }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Grade</dt>
                        <dd class="text-gray-900">{{ $student->grade ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Class time</dt>
                        <dd class="text-gray-900">{{ $student->class_time ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Enrolled on</dt>
                        <dd class="text-gray-900">{{ $student->enrolled_on->format('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Monthly fee</dt>
                        <dd class="font-semibold text-gray-900"><x-money :amount="$student->monthly_fee" /></dd>
                    </div>
                </dl>
            </div>

            {{-- Fee periods table --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Fee periods</h3>
                    <a href="{{ route('fees.index', ['month' => now()->format('Y-m')]) }}"
                       class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                        Manage fees →
                    </a>
                </div>

                {{-- Mobile list --}}
                <div class="divide-y divide-gray-100 sm:hidden">
                    @forelse ($student->fees as $fee)
                        <div class="p-4 space-y-1.5">
                            <div class="flex justify-between items-start">
                                <span class="font-medium text-gray-900">{{ $fee->periodLabel() }}</span>
                                <x-fee-status-badge :status="$fee->status()" />
                            </div>
                            <div class="grid grid-cols-3 text-sm gap-2">
                                <div>
                                    <div class="text-gray-500 text-xs">Fee</div>
                                    <div><x-money :amount="$fee->amount" /></div>
                                </div>
                                <div>
                                    <div class="text-gray-500 text-xs">Paid</div>
                                    <div class="text-emerald-600"><x-money :amount="$fee->paidAmount()" /></div>
                                </div>
                                <div>
                                    <div class="text-gray-500 text-xs">Remaining</div>
                                    <div class="{{ $fee->outstanding() > 0 ? 'text-rose-600 font-medium' : 'text-gray-400' }}">
                                        <x-money :amount="$fee->outstanding()" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-400">No fees billed yet.</div>
                    @endforelse
                </div>

                {{-- Desktop table --}}
                <table class="hidden sm:table min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Month</th>
                            <th class="px-5 py-3 text-right">Fee</th>
                            <th class="px-5 py-3 text-right">Paid</th>
                            <th class="px-5 py-3 text-right">Remaining</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($student->fees as $fee)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-5 py-3 font-medium text-gray-900">{{ $fee->periodLabel() }}</td>
                                <td class="px-5 py-3 text-right"><x-money :amount="$fee->amount" /></td>
                                <td class="px-5 py-3 text-right text-emerald-600"><x-money :amount="$fee->paidAmount()" /></td>
                                <td class="px-5 py-3 text-right {{ $fee->outstanding() > 0 ? 'text-rose-600 font-semibold' : 'text-gray-400' }}">
                                    <x-money :amount="$fee->outstanding()" />
                                </td>
                                <td class="px-5 py-3"><x-fee-status-badge :status="$fee->status()" /></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-gray-400">No fees billed yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
