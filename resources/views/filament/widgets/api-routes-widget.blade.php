<x-filament-widgets::widget>
    {{ $this->form }}

    @if ($responseBody)
        <x-filament::section
            collapsible
            :collapsed="false"
            class="mt-6"
        >
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <span>Response</span>
                    @if ($statusCode)
                        <x-filament::badge
                            :color="$statusCode >= 200 && $statusCode < 300 ? 'success' : ($statusCode >= 400 ? 'danger' : 'warning')"
                        >
                            {{ $statusCode }} {{ $statusText }}
                        </x-filament::badge>
                    @endif
                    @if ($responseTime)
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $responseTime }}ms
                        </span>
                    @endif
                </div>
            </x-slot>

            <x-slot name="headerEnd">
                <x-filament::icon-button
                    icon="heroicon-o-clipboard-document"
                    color="gray"
                    size="sm"
                    x-data="{ copied: false }"
                    x-on:click="
                        navigator.clipboard.writeText(@js($responseBody));
                        copied = true;
                        setTimeout(() => copied = false, 2000);
                        $tooltip('Copied!');
                    "
                    x-tooltip="'Copy response'"
                />
            </x-slot>

            <div class="w-full">
                <pre class="p-4 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 font-mono text-xs overflow-x-auto max-h-96 text-wrap overflow-y-auto">{{ $responseBody }}</pre>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ strlen($responseBody) }} characters
                </div>
            </div>
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
