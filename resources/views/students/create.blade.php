<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Add student') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('students.store') }}" class="bg-white shadow-sm rounded-lg border border-gray-100 p-6 space-y-6">
                @csrf

                @include('students._form')

                <div class="flex items-center gap-4">
                    <x-primary-button>{{ __('Save student') }}</x-primary-button>
                    <a href="{{ route('students.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
