<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit student') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <form method="POST" action="{{ route('students.update', $student) }}" class="bg-white shadow-sm rounded-lg border border-gray-100 p-6 space-y-6">
                @csrf
                @method('PUT')

                @include('students._form')

                <div class="flex items-center gap-4">
                    <x-primary-button>{{ __('Update student') }}</x-primary-button>
                    <a href="{{ route('students.show', $student) }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
                </div>
            </form>

            <form method="POST" action="{{ route('students.destroy', $student) }}"
                  onsubmit="return confirm('Delete this student? Students with billing history cannot be deleted.');"
                  class="bg-white shadow-sm rounded-lg border border-gray-100 p-6">
                @csrf
                @method('DELETE')
                <x-danger-button>{{ __('Delete student') }}</x-danger-button>
            </form>
        </div>
    </div>
</x-app-layout>
