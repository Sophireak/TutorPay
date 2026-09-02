@if (session('status'))
    <div x-data="{ show: true }" x-show="show" x-transition
         class="mb-4 flex items-start justify-between gap-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        <span>{{ session('status') }}</span>
        <button type="button" @click="show = false" class="text-emerald-600 hover:text-emerald-800">&times;</button>
    </div>
@endif
