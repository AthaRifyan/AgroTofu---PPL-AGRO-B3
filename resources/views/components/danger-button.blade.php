<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-tm-md bg-error px-5 py-3 text-label-lg text-on-error shadow-sm transition-all hover:bg-error/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-error focus-visible:ring-offset-2 focus-visible:ring-offset-surface active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60']) }}>
    {{ $slot }}
</button>
