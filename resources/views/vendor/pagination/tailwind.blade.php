@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
         class="flex flex-wrap items-center justify-between gap-3 border-t border-outline-variant/15 pt-4">

        <p class="font-body-sm text-body-sm text-on-surface-variant">
            @if ($paginator->firstItem())
                <span class="tabular-nums text-on-surface">{{ $paginator->firstItem() }}</span>–<span class="tabular-nums text-on-surface">{{ $paginator->lastItem() }}</span>
            @else
                <span class="tabular-nums">{{ $paginator->count() }}</span>
            @endif
            de <span class="tabular-nums text-on-surface">{{ $paginator->total() }}</span> resultados
        </p>

        <div class="flex items-center gap-1">
            {{-- Anterior --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}"
                      class="inline-flex h-8 items-center rounded-obsidian border border-outline-variant/20 px-2.5 font-label-sm text-label-sm text-outline">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}"
                   class="inline-flex h-8 items-center rounded-obsidian border border-outline-variant/30 px-2.5 text-on-surface-variant transition hover:border-primary-container/40 hover:text-on-surface focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container/60">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
            @endif

            {{-- Páginas --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span aria-disabled="true"
                          class="inline-flex h-8 items-center px-2 font-label-sm text-label-sm text-outline">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                  class="inline-flex h-8 min-w-[2rem] items-center justify-center rounded-obsidian bg-primary-container px-2 font-label-md text-label-md tabular-nums text-on-primary-container">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                               class="inline-flex h-8 min-w-[2rem] items-center justify-center rounded-obsidian border border-outline-variant/30 px-2 font-label-md text-label-md tabular-nums text-on-surface-variant transition hover:border-primary-container/40 hover:text-on-surface focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container/60">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Siguiente --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}"
                   class="inline-flex h-8 items-center rounded-obsidian border border-outline-variant/30 px-2.5 text-on-surface-variant transition hover:border-primary-container/40 hover:text-on-surface focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container/60">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
            @else
                <span aria-disabled="true" aria-label="{{ __('pagination.next') }}"
                      class="inline-flex h-8 items-center rounded-obsidian border border-outline-variant/20 px-2.5 font-label-sm text-label-sm text-outline">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
