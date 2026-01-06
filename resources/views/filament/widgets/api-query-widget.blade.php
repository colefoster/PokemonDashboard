<x-filament-widgets::widget>
    {{ $this->form }}

    @if ($responseBody)
        <div class="fi-fo-component-ctn" style="margin-top: 1.5rem;">
            {{-- Response Headers Section - Full Width --}}


            {{-- Two Column Layout for Keys and Body --}}
            <div class="fi-fo-grid" style="display: grid; gap: 1.5rem; margin-top: 1.5rem;">
                <style>
                    @media (min-width: 1024px) {
                        .fi-api-widget-grid {
                            grid-template-columns: repeat(2, minmax(0, 1fr));
                        }
                    }
                </style>
                <div class="fi-api-widget-grid" style="display: grid; gap: 1.5rem;">
                    {{-- Left Column: Response Keys --}}
                    @if ($responseHeaders)
                        <x-filament::section
                            collapsible
                            collapsed
                        >
                            <x-slot name="heading">
                                Response Headers
                            </x-slot>

                            <x-slot name="description">
                                HTTP response headers from the API
                            </x-slot>

                            @include('filament.widgets.partials.response-headers', ['headers' => $responseHeaders])
                        </x-filament::section>
                    @endif

                    {{-- Right Column: Response Body --}}
                    @if ($keyStructure)
                        <x-filament::section
                            collapsible
                            collapsed
                        >
                            <x-slot name="heading">
                                Response Keys
                            </x-slot>

                            <x-slot name="description">
                                Top-level keys from the JSON response
                            </x-slot>

                            @include('filament.widgets.partials.response-keys', ['keyStructure' => $keyStructure])
                        </x-filament::section>
                    @endif
                </div>
                    <x-filament::section
                        collapsible
                        collapsed
                    >
                        <x-slot name="heading">
                            Response Body
                        </x-slot>

                        <x-slot name="description">
                            @if ($statusCode)
                                <x-filament::badge
                                    :color="$statusCode >= 200 && $statusCode < 300 ? 'success' : ($statusCode >= 400 ? 'danger' : 'warning')"
                                >
                                    {{ $statusCode }} {{ $statusText }}
                                </x-filament::badge>
                            @endif
                        </x-slot>

                        <x-slot name="headerEnd">
                            <button
                                type="button"
                                class="fi-btn fi-btn-sm fi-color-gray"
                                x-data="{ copied: false }"
                                x-on:click.prevent="
                                    navigator.clipboard.writeText(@js($responseBody));
                                    copied = true;
                                    setTimeout(() => copied = false, 2000);
                                "
                                style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.375rem 0.75rem; font-size: 0.875rem; border-radius: 0.375rem;"
                            >
                                <svg class="fi-btn-icon" style="width: 1rem; height: 1rem;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z" />
                                </svg>
                                <span x-text="copied ? 'Copied!' : 'Copy'">Copy</span>
                            </button>
                        </x-slot>

                        @include('filament.widgets.partials.response-body', [
                            'responseBody' => $responseBody,
                            'statusCode' => $statusCode,
                            'statusText' => $statusText
                        ])
                    </x-filament::section>
                </div>
            </div>
        </div>
    @endif
</x-filament-widgets::widget>
