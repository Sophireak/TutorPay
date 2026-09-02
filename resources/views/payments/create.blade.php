<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Record payment') }} — {{ $fee->student->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-stat-card label="Billing month" :value="$fee->periodLabel()" />
                <x-stat-card label="Fee" :value="config('tutorpay.currency_symbol').number_format((float) $fee->amount, 2)" />
                <x-stat-card label="Outstanding" tone="negative" :value="config('tutorpay.currency_symbol').number_format($fee->outstanding(), 2)" />
            </div>

            <form method="POST" action="{{ route('payments.store') }}" class="bg-white shadow-sm rounded-lg border border-gray-100 p-6 space-y-6">
                @csrf
                <input type="hidden" name="fee_id" value="{{ $fee->id }}">

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <x-input-label for="amount" :value="__('Amount')" />
                        <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full"
                                      :value="old('amount', number_format($fee->outstanding(), 2, '.', ''))" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('amount')" />
                    </div>

                    <div>
                        <x-input-label for="paid_on" :value="__('Paid on')" />
                        <x-text-input id="paid_on" name="paid_on" type="date" class="mt-1 block w-full"
                                      :value="old('paid_on', now()->toDateString())" required />
                        <x-input-error class="mt-2" :messages="$errors->get('paid_on')" />
                    </div>

                    <div>
                        <x-input-label for="method" :value="__('Method')" />
                        <select id="method" name="method" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($methods as $method)
                                <option value="{{ $method->value }}" @selected(old('method') === $method->value)>{{ $method->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('method')" />
                    </div>

                    <div>
                        <x-input-label for="reference" :value="__('Reference')" />
                        <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full" :value="old('reference')" />
                        <x-input-error class="mt-2" :messages="$errors->get('reference')" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="notes" :value="__('Notes')" />
                        <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <x-primary-button>{{ __('Save payment') }}</x-primary-button>
                    <a href="{{ route('students.show', $fee->student) }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
