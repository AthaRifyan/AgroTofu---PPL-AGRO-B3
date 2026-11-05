@if (session('success') || session('error'))
    <div class="mb-4 space-y-2">
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition class="flex items-start gap-2 rounded-tm-md bg-tertiary-fixed px-4 py-3 text-label-md text-on-tertiary-fixed" role="status">
                <x-icon name="check_circle" class="shrink-0 text-[18px]" />
                <span class="flex-1">{{ session('success') }}</span>
                <button type="button" x-on:click="show = false" aria-label="Tutup" class="shrink-0"><x-icon name="close" class="text-[18px]" /></button>
            </div>
        @endif
        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-transition class="flex items-start gap-2 rounded-tm-md bg-error-container px-4 py-3 text-label-md text-on-error-container" role="alert">
                <x-icon name="error" class="shrink-0 text-[18px]" />
                <span class="flex-1">{{ session('error') }}</span>
                <button type="button" x-on:click="show = false" aria-label="Tutup" class="shrink-0"><x-icon name="close" class="text-[18px]" /></button>
            </div>
        @endif
    </div>
@endif
