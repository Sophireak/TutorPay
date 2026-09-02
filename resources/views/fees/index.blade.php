<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Monthly Fees') }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $month->format('F Y') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8" x-data="{ addFee: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            <x-flash />

            {{-- Summary stats --}}
            <div class="grid gap-3 grid-cols-2 sm:grid-cols-3">
                <x-stat-card label="Billed"
                             :value="config('tutorpay.currency_symbol').number_format($summary['billed'], 2)"
                             hint="{{ $summary['students_billed'] }} student(s)" />
                <x-stat-card label="Collected" tone="positive"
                             :value="config('tutorpay.currency_symbol').number_format($summary['collected'], 2)"
                             hint="{{ $summary['fully_paid'] }} fully paid" />
                <x-stat-card label="Outstanding" tone="{{ $summary['outstanding'] > 0 ? 'negative' : 'default' }}"
                             :value="config('tutorpay.currency_symbol').number_format($summary['outstanding'], 2)"
                             class="col-span-2 sm:col-span-1" />
            </div>

            {{-- Controls: month selector + generate --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-4 space-y-4">

                <div class="flex flex-wrap gap-4 items-end">
                    {{-- Month picker --}}
                    <form method="GET" action="{{ route('fees.index') }}" class="flex items-end gap-3">
                        <div>
                            <x-input-label for="month" :value="__('Billing month')" />
                            <x-text-input id="month" name="month" type="month" class="mt-1"
                                          :value="$month->format('Y-m')" />
                        </div>
                        <x-primary-button>{{ __('Show') }}</x-primary-button>
                    </form>

                    <div class="h-8 border-l border-gray-200 hidden sm:block self-end"></div>

                    {{-- Generate fees --}}
                    <form method="POST" action="{{ route('fees.generate') }}" class="flex items-end gap-3">
                        @csrf
                        <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                        <div>
                            <x-input-label for="due_day" :value="__('Due day')" />
                            <x-text-input id="due_day" name="due_day" type="number" min="1" max="28"
                                          class="mt-1 w-20"
                                          :value="old('due_day', config('tutorpay.default_due_day'))" />
                        </div>
                        <x-secondary-button type="submit">
                            <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            {{ __('Generate for active students') }}
                        </x-secondary-button>
                    </form>

                    {{-- Add single fee toggle --}}
                    <x-secondary-button type="button" x-on:click="addFee = !addFee" class="self-end">
                        <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ __('Add single fee') }}
                    </x-secondary-button>
                </div>

                {{-- Add single fee form (hidden by default) --}}
                <div x-show="addFee" x-cloak x-transition
                     class="border-t border-gray-100 pt-4">
                    <form method="POST" action="{{ route('fees.store') }}"
                          class="flex flex-wrap gap-4 items-end">
                        @csrf

                        <div class="grow basis-48">
                            <x-input-label for="student_id" :value="__('Student')" />
                            <select id="student_id" name="student_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                @foreach ($students as $student)
                                    <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>
                                        {{ $student->name }} ({{ $student->student_code }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-1" :messages="$errors->get('student_id')" />
                        </div>

                        <div class="basis-32">
                            <x-input-label for="period_month" :value="__('Month')" />
                            <x-text-input id="period_month" name="period_month" type="month"
                                          class="mt-1 block w-full"
                                          :value="old('period_month', $month->format('Y-m'))" />
                            <x-input-error class="mt-1" :messages="$errors->get('period_month')" />
                        </div>

                        <div class="basis-28">
                            <x-input-label for="amount" :value="__('Amount')" />
                            <div class="relative mt-1">
                                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-500 text-sm">
                                    {{ config('tutorpay.currency_symbol') }}
                                </span>
                                <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01"
                                              class="block w-full pl-7" :value="old('amount')" />
                            </div>
                            <x-input-error class="mt-1" :messages="$errors->get('amount')" />
                        </div>

                        <x-primary-button>{{ __('Add fee') }}</x-primary-button>
                    </form>
                </div>
            </div>

            {{-- Fee table --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">

                {{-- Mobile cards --}}
                <div class="divide-y divide-gray-100 sm:hidden">
                    @forelse ($fees as $fee)
                        <div class="p-4 space-y-2">
                            <div class="flex justify-between items-start">
                                <div>
                                    <a href="{{ route('students.show', $fee->student) }}"
                                       class="font-semibold text-gray-900 hover:text-indigo-600">
                                        {{ $fee->student->name }}
                                    </a>
                                    <div class="text-xs text-gray-500 mt-0.5 space-x-2">
                                        <span class="font-mono">{{ $fee->student->student_code }}</span>
                                        @if ($fee->student->grade)
                                            <span>&middot; Grade {{ $fee->student->grade }}</span>
                                        @endif
                                    </div>
                                    @if ($fee->student->class_time)
                                        <div class="text-xs text-gray-400">{{ $fee->student->class_time }}</div>
                                    @endif
                                </div>
                                <x-fee-status-badge :status="$fee->status()" />
                            </div>
                            <div class="grid grid-cols-3 text-sm gap-2 pt-1">
                                <div>
                                    <div class="text-xs text-gray-500">Fee</div>
                                    <div class="font-medium"><x-money :amount="$fee->amount" /></div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Paid</div>
                                    <div class="text-emerald-600"><x-money :amount="$fee->paidAmount()" /></div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Remaining</div>
                                    <div class="{{ $fee->outstanding() > 0 ? 'text-rose-600 font-medium' : 'text-gray-400' }}">
                                        <x-money :amount="$fee->outstanding()" />
                                    </div>
                                </div>
                            </div>
                            @if ($fee->paidAmount() <= 0)
                                <form method="POST" action="{{ route('fees.destroy', $fee) }}"
                                      x-data
                                      @submit.prevent="if(confirm('Remove this fee?')) $el.submit()"
                                      class="pt-1">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-xs text-rose-600 hover:text-rose-800 font-medium">
                                        Remove fee
                                    </button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-400">
                            No fees for {{ $month->format('F Y') }} yet.
                            Use "Generate for active students" to create them.
                        </div>
                    @endforelse
                </div>

                {{-- Desktop table --}}
                <table class="hidden sm:table min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Student</th>
                            <th class="px-5 py-3">Grade</th>
                            <th class="px-5 py-3">Class time</th>
                            <th class="px-5 py-3 text-right">Fee</th>
                            <th class="px-5 py-3 text-right">Paid</th>
                            <th class="px-5 py-3 text-right">Remaining</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($fees as $fee)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-5 py-3">
                                    <a href="{{ route('students.show', $fee->student) }}"
                                       class="font-semibold text-gray-900 hover:text-indigo-600">
                                        {{ $fee->student->name }}
                                    </a>
                                    <div class="text-xs text-gray-400 font-mono">{{ $fee->student->student_code }}</div>
                                </td>
                                <td class="px-5 py-3 text-gray-600">{{ $fee->student->grade ?? '—' }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ $fee->student->class_time ?? '—' }}</td>
                                <td class="px-5 py-3 text-right font-medium"><x-money :amount="$fee->amount" /></td>
                                <td class="px-5 py-3 text-right text-emerald-600"><x-money :amount="$fee->paidAmount()" /></td>
                                <td class="px-5 py-3 text-right {{ $fee->outstanding() > 0 ? 'text-rose-600 font-semibold' : 'text-gray-400' }}">
                                    <x-money :amount="$fee->outstanding()" />
                                </td>
                                <td class="px-5 py-3"><x-fee-status-badge :status="$fee->status()" /></td>
                                <td class="px-5 py-3 text-right whitespace-nowrap">
                                    @if ($fee->paidAmount() <= 0)
                                        <form method="POST" action="{{ route('fees.destroy', $fee) }}"
                                              class="inline"
                                              x-data
                                              @submit.prevent="if(confirm('Remove this fee?')) $el.submit()">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="text-rose-600 hover:text-rose-800 font-medium">
                                                Remove
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-12 text-center text-gray-400">
                                    No fees for {{ $month->format('F Y') }} yet.
                                    Use "Generate for active students" to create them.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
