<div class="grid gap-6 sm:grid-cols-2">
    <div>
        <x-input-label for="name" :value="__('Name')" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                      :value="old('name', $student->name)" required autofocus />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="guardian_name" :value="__('Guardian name')" />
        <x-text-input id="guardian_name" name="guardian_name" type="text" class="mt-1 block w-full"
                      :value="old('guardian_name', $student->guardian_name)" />
        <x-input-error class="mt-2" :messages="$errors->get('guardian_name')" />
    </div>

    <div>
        <x-input-label for="phone" :value="__('Phone')" />
        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full"
                      :value="old('phone', $student->phone)" />
        <x-input-error class="mt-2" :messages="$errors->get('phone')" />
    </div>

    <div>
        <x-input-label for="email" :value="__('Email')" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                      :value="old('email', $student->email)" />
        <x-input-error class="mt-2" :messages="$errors->get('email')" />
    </div>

    <div>
        <x-input-label for="batch" :value="__('Class / batch')" />
        <x-text-input id="batch" name="batch" type="text" class="mt-1 block w-full"
                      :value="old('batch', $student->batch)" placeholder="Grade 10 - Physics" />
        <x-input-error class="mt-2" :messages="$errors->get('batch')" />
    </div>

    <div>
        <x-input-label for="monthly_fee" :value="__('Monthly fee')" />
        <x-text-input id="monthly_fee" name="monthly_fee" type="number" step="0.01" min="0" class="mt-1 block w-full"
                      :value="old('monthly_fee', $student->monthly_fee)" required />
        <x-input-error class="mt-2" :messages="$errors->get('monthly_fee')" />
    </div>

    <div>
        <x-input-label for="status" :value="__('Status')" />
        <select id="status" name="status"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            @foreach (\App\Enums\StudentStatus::cases() as $case)
                <option value="{{ $case->value }}"
                    @selected(old('status', $student->status?->value) === $case->value)>{{ $case->label() }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('status')" />
    </div>

    <div>
        <x-input-label for="enrolled_on" :value="__('Enrolled on')" />
        <x-text-input id="enrolled_on" name="enrolled_on" type="date" class="mt-1 block w-full"
                      :value="old('enrolled_on', $student->enrolled_on?->format('Y-m-d'))" required />
        <x-input-error class="mt-2" :messages="$errors->get('enrolled_on')" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="notes" :value="__('Notes')" />
        <textarea id="notes" name="notes" rows="3"
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notes', $student->notes) }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
    </div>
</div>
