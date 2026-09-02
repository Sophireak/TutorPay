<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('students.index') }}"
               class="text-gray-400 hover:text-gray-600 transition">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Add Student') }}</h2>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('students.store') }}"
                  class="bg-white shadow-sm rounded-xl border border-gray-100 p-6 space-y-6">
                @csrf

                @include('students._form')

                <div class="flex items-center gap-4 pt-2 border-t border-gray-100">
                    <x-primary-button>{{ __('Save student') }}</x-primary-button>
                    <a href="{{ route('students.index') }}"
                       class="text-sm text-gray-500 hover:text-gray-700 transition">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
