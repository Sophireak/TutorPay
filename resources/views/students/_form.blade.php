<div class="grid gap-5 sm:grid-cols-2">

    {{-- Name --}}
    <div class="sm:col-span-2">
        <x-input-label for="name" :value="__('Full name')" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                      :value="old('name', $student->name)" required autofocus autocomplete="off" />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    {{-- Student code --}}
    <div>
        <x-input-label for="student_code" :value="__('Student code')" />
        <x-text-input id="student_code" name="student_code" type="text" class="mt-1 block w-full font-mono"
                      :value="old('student_code', $student->student_code)"
                      placeholder="Auto-generated if left blank" />
        <p class="mt-1 text-xs text-gray-400">Unique identifier, e.g. STU-0001</p>
        <x-input-error class="mt-2" :messages="$errors->get('student_code')" />
    </div>

    {{-- Grade --}}
    <div>
        <x-input-label for="grade" :value="__('Grade')" />
        <x-text-input id="grade" name="grade" type="text" class="mt-1 block w-full"
                      :value="old('grade', $student->grade)"
                      placeholder="e.g. 10 or Form 4" />
        <x-input-error class="mt-2" :messages="$errors->get('grade')" />
    </div>

    {{-- Class time --}}
    <div>
        <x-input-label for="class_time" :value="__('Class time')" />
        <x-text-input id="class_time" name="class_time" type="text" class="mt-1 block w-full"
                      :value="old('class_time', $student->class_time)"
                      placeholder="e.g. Sat 16:00–18:00" />
        <x-input-error class="mt-2" :messages="$errors->get('class_time')" />
    </div>

    {{-- Monthly fee --}}
    <div>
        <x-input-label for="monthly_fee" :value="__('Monthly fee')" />
        <div class="relative mt-1">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-500 text-sm">
                {{ config('tutorpay.currency_symbol') }}
            </span>
            <x-text-input id="monthly_fee" name="monthly_fee" type="number" step="0.01" min="0"
                          class="block w-full pl-7"
                          :value="old('monthly_fee', $student->monthly_fee)" required />
        </div>
        <x-input-error class="mt-2" :messages="$errors->get('monthly_fee')" />
    </div>

    {{-- Status --}}
    <div>
        <x-input-label for="status" :value="__('Status')" />
        <select id="status" name="status"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
            @foreach (\App\Enums\StudentStatus::cases() as $case)
                <option value="{{ $case->value }}"
                    @selected(old('status', $student->status?->value) === $case->value)>{{ $case->label() }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('status')" />
    </div>

    {{-- Enrolled on (kept for billing logic; shown but secondary) --}}
    <div>
        <x-input-label for="enrolled_on" :value="__('Enrolled on')" />
        <x-text-input id="enrolled_on" name="enrolled_on" type="date" class="mt-1 block w-full"
                      :value="old('enrolled_on', $student->enrolled_on?->format('Y-m-d'))" required />
        <x-input-error class="mt-2" :messages="$errors->get('enrolled_on')" />
    </div>

</div>
