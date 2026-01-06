<div style="min-height: 500px; max-height: 500px; overflow-y: auto;">
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        @foreach ($headers as $header => $values)
            <div>
                {{-- Parent header badge --}}
                <div style="margin-bottom: 0.5rem;">
                    <x-filament::badge
                        color="gray"
                        size="md"
                    >
                        {{ $header }}
                    </x-filament::badge>
                </div>

                {{-- Indented value badges --}}
                <div style="margin-left: 1.5rem; display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    @if (is_array($values))
                        @foreach ($values as $value)
                            <x-filament::badge
                                color="info"
                                size="sm"
                            >
                                {{ $value }}
                            </x-filament::badge>
                        @endforeach
                    @else
                        <x-filament::badge
                            color="info"
                            size="sm"
                        >
                            {{ $values }}
                        </x-filament::badge>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
