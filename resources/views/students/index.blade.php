<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Students') }}</h2>
            <a href="{{ route('students.create') }}"
               class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('Add Student') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
            <x-flash />

            {{-- Search & Filter bar --}}
            <form method="GET" action="{{ route('students.index') }}"
                  class="bg-white shadow-sm rounded-xl border border-gray-100 p-4">
                <div class="flex flex-wrap gap-3 items-end">
                    <div class="grow min-w-0 basis-48">
                        <x-input-label for="search" :value="__('Search')" />
                        <x-text-input id="search" name="search" type="search" class="mt-1 block w-full"
                                      :value="$search" placeholder="Name or student code" />
                    </div>

                    <div class="basis-32">
                        <x-input-label for="grade" :value="__('Grade')" />
                        <select id="grade" name="grade"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">All grades</option>
                            @foreach ($grades as $g)
                                <option value="{{ $g }}" @selected($grade === $g)>{{ $g }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="basis-32">
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">All</option>
                            @foreach (\App\Enums\StudentStatus::cases() as $case)
                                <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex gap-2 items-end">
                        <x-primary-button>{{ __('Filter') }}</x-primary-button>
                        @if ($search || $grade || $status)
                            <a href="{{ route('students.index') }}"
                               class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                                Clear
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            {{-- Student list --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">

                {{-- Mobile cards --}}
                <div class="divide-y divide-gray-100 sm:hidden">
                    @forelse ($students as $student)
                        @php $balance = max((float) $student->billed_total - (float) $student->paid_total, 0); @endphp
                        <div class="p-4 space-y-2">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <a href="{{ route('students.show', $student) }}"
                                       class="font-semibold text-gray-900 hover:text-indigo-600">
                                        {{ $student->name }}
                                    </a>
                                    <div class="text-xs text-gray-500 mt-0.5 space-x-2">
                                        <span class="font-mono">{{ $student->student_code }}</span>
                                        @if ($student->grade)
                                            <span>&middot; Grade {{ $student->grade }}</span>
                                        @endif
                                    </div>
                                    @if ($student->class_time)
                                        <div class="text-xs text-gray-400 mt-0.5">{{ $student->class_time }}</div>
                                    @endif
                                </div>
                                <span class="shrink-0 inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium
                                    {{ $student->isActive() ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $student->status->label() }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Monthly fee</span>
                                <span class="font-semibold"><x-money :amount="$student->monthly_fee" /></span>
                            </div>
                            @if ($balance > 0)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">Outstanding</span>
                                    <span class="font-semibold text-rose-600"><x-money :amount="$balance" /></span>
                                </div>
                            @endif
                            <div class="flex items-center gap-3 pt-1">
                                <a href="{{ route('students.show', $student) }}"
                                   class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">View</a>
                                <a href="{{ route('students.edit', $student) }}"
                                   class="text-xs text-gray-600 hover:text-gray-800 font-medium">Edit</a>
                                <form method="POST" action="{{ route('students.toggle', $student) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="text-xs font-medium {{ $student->isActive() ? 'text-amber-600 hover:text-amber-800' : 'text-emerald-600 hover:text-emerald-800' }}">
                                        {{ $student->isActive() ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-400">
                            No students found. <a href="{{ route('students.create') }}" class="text-indigo-600 hover:underline">Add your first student.</a>
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
                            <th class="px-5 py-3 text-right">Monthly fee</th>
                            <th class="px-5 py-3 text-right">Outstanding</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($students as $student)
                            @php $balance = max((float) $student->billed_total - (float) $student->paid_total, 0); @endphp
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-5 py-3">
                                    <a href="{{ route('students.show', $student) }}"
                                       class="font-semibold text-gray-900 hover:text-indigo-600">{{ $student->name }}</a>
                                    <div class="text-xs text-gray-400 font-mono mt-0.5">{{ $student->student_code }}</div>
                                </td>
                                <td class="px-5 py-3 text-gray-600">{{ $student->grade ?? '—' }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ $student->class_time ?? '—' }}</td>
                                <td class="px-5 py-3 text-right font-medium"><x-money :amount="$student->monthly_fee" /></td>
                                <td class="px-5 py-3 text-right {{ $balance > 0 ? 'text-rose-600 font-semibold' : 'text-gray-400' }}">
                                    <x-money :amount="$balance" />
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium
                                        {{ $student->isActive() ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-500' }}">
                                        {{ $student->status->label() }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('students.edit', $student) }}"
                                       class="text-indigo-600 hover:text-indigo-800 font-medium mr-3">Edit</a>
                                    <form method="POST" action="{{ route('students.toggle', $student) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="font-medium {{ $student->isActive() ? 'text-amber-600 hover:text-amber-800' : 'text-emerald-600 hover:text-emerald-800' }}">
                                            {{ $student->isActive() ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-gray-400">
                                    No students found.
                                    @unless ($search || $grade || $status)
                                        <a href="{{ route('students.create') }}" class="text-indigo-600 hover:underline">Add your first student.</a>
                                    @endunless
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $students->links() }}
        </div>
    </div>
</x-app-layout>
