<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Students') }}</h2>
            <a href="{{ route('students.create') }}" class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                {{ __('Add student') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <form method="GET" action="{{ route('students.index') }}" class="flex flex-wrap items-end gap-3 bg-white shadow-sm rounded-lg border border-gray-100 p-4">
                <div class="grow min-w-48">
                    <x-input-label for="search" :value="__('Search')" />
                    <x-text-input id="search" name="search" type="search" class="mt-1 block w-full"
                                  :value="$search" placeholder="Name, guardian, phone or batch" />
                </div>

                <div>
                    <x-input-label for="status" :value="__('Status')" />
                    <select id="status" name="status" class="mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All</option>
                        @foreach (\App\Enums\StudentStatus::cases() as $case)
                            <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <x-primary-button>{{ __('Filter') }}</x-primary-button>
            </form>

            <div class="bg-white shadow-sm rounded-lg border border-gray-100 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Student</th>
                            <th class="px-5 py-3">Batch</th>
                            <th class="px-5 py-3 text-right">Monthly fee</th>
                            <th class="px-5 py-3 text-right">Billed</th>
                            <th class="px-5 py-3 text-right">Paid</th>
                            <th class="px-5 py-3 text-right">Outstanding</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($students as $student)
                            @php $balance = max((float) $student->billed_total - (float) $student->paid_total, 0); @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3">
                                    <a href="{{ route('students.show', $student) }}" class="font-medium text-gray-800 hover:text-indigo-600">{{ $student->name }}</a>
                                    @if ($student->guardian_name)
                                        <div class="text-xs text-gray-500">{{ $student->guardian_name }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-gray-600">{{ $student->batch ?? '—' }}</td>
                                <td class="px-5 py-3 text-right"><x-money :amount="$student->monthly_fee" /></td>
                                <td class="px-5 py-3 text-right"><x-money :amount="$student->billed_total ?? 0" /></td>
                                <td class="px-5 py-3 text-right text-emerald-600"><x-money :amount="$student->paid_total ?? 0" /></td>
                                <td class="px-5 py-3 text-right {{ $balance > 0 ? 'text-rose-600 font-semibold' : 'text-gray-500' }}">
                                    <x-money :amount="$balance" />
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $student->isActive() ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $student->status->label() }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-8 text-center text-gray-500">No students yet. Add your first student to start billing.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $students->links() }}
        </div>
    </div>
</x-app-layout>
