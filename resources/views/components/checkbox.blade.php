@props(['name', 'label', 'checked' => false])

<label class="inline-flex cursor-pointer select-none items-center gap-2.5">
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $checked))
           class="h-5 w-5 rounded-tm-sm border-outline text-secondary shadow-sm focus:ring-2 focus:ring-secondary focus:ring-offset-0">
    <span class="text-label-md text-on-surface">{{ $label }}</span>
</label>
