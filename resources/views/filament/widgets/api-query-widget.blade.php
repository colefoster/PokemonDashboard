<x-filament-widgets::widget>
    {{ $this->form }}

    @if ($responseBody)
        <div class="grid grid-cols-1 gap-6 mt-6">
            {{-- Response Headers Section --}}
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

                    <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($responseHeaders as $header => $values)
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                <dt class="text-sm font-semibold leading-6 text-gray-900 dark:text-white">
                                    {{ $header }}
                                </dt>
                                <dd class="mt-1 text-sm leading-6 text-gray-700 dark:text-gray-300 sm:col-span-2 sm:mt-0 font-mono break-all">
                                    @if (is_array($values))
                                        {{ implode(', ', $values) }}
                                    @else
                                        {{ $values }}
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </x-filament::section>
            @endif

            {{-- Response Body Section --}}
            <x-filament::section
                collapsible
                :collapsed="false"
            >
                <x-slot name="heading">
                    <div class="flex items-center gap-3">
                        <span>Response Body</span>
                        @if ($statusCode)
                            <x-filament::badge
                                :color="$statusCode >= 200 && $statusCode < 300 ? 'success' : ($statusCode >= 400 ? 'danger' : 'warning')"
                            >
                                {{ $statusCode }} {{ $statusText }}
                            </x-filament::badge>
                        @endif
                    </div>
                </x-slot>

                <x-slot name="headerEnd">
                    <x-filament::button
                        color="gray"
                        size="sm"
                        icon="heroicon-o-clipboard-document"
                        x-data="{ copied: false }"
                        x-on:click="
                            navigator.clipboard.writeText(@js($responseBody));
                            copied = true;
                            setTimeout(() => copied = false, 2000);
                        "
                    >
                        <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                    </x-filament::button>
                </x-slot>

                <div class="w-full">
                    <textarea
                        readonly
                        rows="20"
                        class="block w-full max-w-full min-w-full resize-y rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 font-mono text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >{{ $responseBody }}</textarea>

                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        {{ strlen($responseBody) }} characters
                    </div>
                </div>
            </x-filament::section>
        </div>
    @endif
</x-filament-widgets::widget>
