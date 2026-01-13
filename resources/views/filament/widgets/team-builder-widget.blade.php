<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Pokémon Team Builder
                </h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Build and analyze your perfect Pokémon team
                </p>
            </div>
            <div>
                <a
                    href="{{ route('teambuilder') }}"
                    target="_blank"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors text-sm"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    Open Team Builder
                </a>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>