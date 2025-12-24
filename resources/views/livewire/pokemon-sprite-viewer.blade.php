<div>
    <x-filament::fieldset>
        <x-slot name="label">
            Sprites
        </x-slot>

        <div class="space-y-4">


            <div class="flex flex-col gap-2">
                <x-filament::button.group>
                    <x-filament::button
                        :color="$variant === 'default' ? 'primary' : 'gray'"
                        :outlined="$variant !== 'default'"
                        wire:click="setVariant('default')"
                        size="sm"
                    >
                        Regular
                    </x-filament::button>
                    <x-filament::button
                        :color="$variant === 'shiny' ? 'primary' : 'gray'"
                        :outlined="$variant !== 'shiny'"
                        wire:click="setVariant('shiny')"
                        size="sm"
                    >
                        Shiny
                    </x-filament::button>
                </x-filament::button.group>
            </div>

            {{-- Sprite Display --}}
            <div class="flex flex-row ">
                <img
                    src="{{ $this->frontSpriteUrl }}"
                    alt="Pokemon sprite"
                    class="w-auto h-auto  transition-all duration-300 ease-in-out"
                    style="image-rendering: pixelated;"
                >
                <img
                    src="{{ $this->backSpriteUrl }}"
                    alt="Pokemon sprite"
                    class="w-auto h-auto  transition-all duration-300 ease-in-out"
                    style="image-rendering: pixelated;"
                >
            </div>
        </div>
    </x-filament::fieldset>
</div>
