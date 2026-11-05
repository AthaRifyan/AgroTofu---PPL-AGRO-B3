<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-tm-md bg-surface-container-lowest px-5 py-3 text-label-lg text-on-surface shadow-sm transition-all hover:bg-surface-container focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary focus-visible:ring-offset-2 focus-visible:ring-offset-surface active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60']) }}>
    {{ $slot }}
</button>
