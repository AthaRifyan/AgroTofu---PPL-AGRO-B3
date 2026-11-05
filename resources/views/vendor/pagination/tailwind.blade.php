@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-col items-center justify-between gap-3 bg-surface-container-low px-4 py-3 sm:flex-row">
        <span class="text-body-sm text-on-surface-variant">
            Menampilkan <strong class="text-on-surface">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong> dari <strong class="text-on-surface">{{ $paginator->total() }}</strong> data
        </span>

        <div class="flex items-center gap-2">
            @if ($paginator->onFirstPage())
                <span class="rounded-tm-md bg-surface-container-lowest px-3 py-1.5 text-label-md text-outline opacity-60 shadow-sm">Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded-tm-md bg-surface-container-lowest px-3 py-1.5 text-label-md text-on-surface shadow-sm transition-colors hover:bg-surface-container focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary">Sebelumnya</a>
            @endif

            <span class="flex h-7 min-w-7 items-center justify-center rounded-tm-md bg-primary-container px-2 text-label-sm text-on-primary-container">{{ $paginator->currentPage() }}</span>
            <span class="text-body-sm text-on-surface-variant">/ {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-tm-md bg-surface-container-lowest px-3 py-1.5 text-label-md text-on-surface shadow-sm transition-colors hover:bg-surface-container focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary">Berikutnya</a>
            @else
                <span class="rounded-tm-md bg-surface-container-lowest px-3 py-1.5 text-label-md text-outline opacity-60 shadow-sm">Berikutnya</span>
            @endif
        </div>
    </nav>
@endif
