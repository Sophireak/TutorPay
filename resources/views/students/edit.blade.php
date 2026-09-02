<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('students.show', $student) }}"
               class="text-gray-400 hover:text-gray-600 transition">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Student') }}</h2>
                <p class="text-sm text-gray-500 font-mono">{{ $student->student_code }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            {{-- Edit form --}}
            <form method="POST" action="{{ route('students.update', $student) }}"
                  class="bg-white shadow-sm rounded-xl border border-gray-100 p-6 space-y-6">
                @csrf
                @method('PUT')

                @include('students._form')

                <div class="flex items-center gap-4 pt-2 border-t border-gray-100">
                    <x-primary-button>{{ __('Update student') }}</x-primary-button>
                    <a href="{{ route('students.show', $student) }}"
                       class="text-sm text-gray-500 hover:text-gray-700 transition">{{ __('Cancel') }}</a>
                </div>
            </form>

            {{-- Toggle activation --}}
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5"
                 x-data="{ confirm: false }">
                <h3 class="font-medium text-gray-900 mb-1">Student status</h3>
                <p class="text-sm text-gray-500 mb-4">
                    @if ($student->isActive())
                        Deactivating this student will exclude them from future fee generation.
                    @else
                        Activating this student will include them in future fee generation.
                    @endif
                </p>

                <form method="POST" action="{{ route('students.toggle', $student) }}">
                    @csrf
                    @method('PATCH')
                    @if ($student->isActive())
                        <button type="submit"
                                class="inline-flex items-center rounded-md border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-100 transition">
                            Deactivate student
                        </button>
                    @else
                        <button type="submit"
                                class="inline-flex items-center rounded-md border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-100 transition">
                            Activate student
                        </button>
                    @endif
                </form>
            </div>

            {{-- Danger zone: delete --}}
            @unless ($student->fees()->exists())
                <div class="bg-white shadow-sm rounded-xl border border-rose-100 p-5">
                    <h3 class="font-medium text-rose-800 mb-1">Danger zone</h3>
                    <p class="text-sm text-gray-500 mb-4">Permanently remove this student. This cannot be undone. Students with billing history cannot be deleted — deactivate them instead.</p>
                    <form method="POST" action="{{ route('students.destroy', $student) }}"
                          x-data
                          @submit.prevent="if(confirm('Delete {{ addslashes($student->name) }}? This cannot be undone.')) $el.submit()">
                        @csrf
                        @method('DELETE')
                        <x-danger-button>{{ __('Delete student') }}</x-danger-button>
                    </form>
                </div>
            @endunless

        </div>
    </div>
</x-app-layout>
