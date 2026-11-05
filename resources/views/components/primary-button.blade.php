<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-tm-md bg-primary-container px-6 py-3.5 text-label-lg text-on-primary-container shadow-md transition-all hover:bg-secondary-container hover:shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary focus-visible:ring-offset-2 focus-visible:ring-offset-surface active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-70']) }}>
    {{ $slot }}
</button>
