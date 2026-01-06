<div class="fi-infolist-entry-wrapper">
    <div style="min-height: 500px; max-height: 500px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.75rem;">
        @foreach ($keyStructure as $key => $subKeys)
            <div>
                {{-- Parent key badge --}}
                <div style="margin-bottom: 0.5rem;">
                    <x-filament::badge
                        color="primary"
                        size="md"
                    >
                        {{ $key }}
                    </x-filament::badge>
                </div>

                {{-- Indented subkey badges --}}
                @if (!empty($subKeys) && is_array($subKeys))
                    <div style="margin-left: 1.5rem; display: flex; flex-wrap: wrap; gap: 0.5rem;">
                        @foreach (array_keys($subKeys) as $subKey)
                            <x-filament::badge
                                color="info"
                                size="sm"
                            >
                                {{ $subKey }}
                            </x-filament::badge>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div style="margin-top: 1rem; font-size: 0.75rem; line-height: 1rem; opacity: 0.7;">
        {{ count($keyStructure) }} {{ Str::plural('key', count($keyStructure)) }} found
    </div>
</div>
