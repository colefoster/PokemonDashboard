<div class="w-full overflow-hidden">
    <x-filament::fieldset>
        <x-slot name="label">
            Base Stats
        </x-slot>

        <div class="grid grid-cols-[2fr_1fr] items-center gap-4">
            <div class="relative h-64 w-full overflow-hidden">
                <canvas
                    class="max-w-full h-full"
                    x-data="{
                        init() {
                            const chartData = @js($this->getChartData());
                            const chartOptions = @js($this->getChartOptions());

                            // Create chart instance (store directly on element, not in Alpine data)
                            const chartInstance = new Chart(this.$el, {
                                type: 'radar',
                                data: chartData,
                                options: chartOptions
                            });

                            // Function to update colors
                            const updateColors = (theme = null) => {
                                // Detect current theme - use event detail if provided, otherwise check DOM
                                let isDark;
                                if (theme !== null) {
                                    // Use the theme from the event
                                    isDark = (theme === 'dark');
                                } else {
                                    // Fallback to checking the DOM class
                                    isDark = document.documentElement.classList.contains('dark');
                                }

                                console.log('Updating chart colors, theme:', theme, 'isDark:', isDark);

                                // Theme-aware colors
                                const gridColor = isDark ? 'rgba(255, 255, 255, 0.2)' : 'rgba(0, 0, 0, 0.1)';
                                const angleLinesColor = isDark ? 'rgba(255, 255, 255, 0.2)' : 'rgba(0, 0, 0, 0.1)';
                                const labelColor = isDark ? '#ffffff' : '#000000';

                                // Update chart colors
                                chartInstance.options.scales.r.grid.color = gridColor;
                                chartInstance.options.scales.r.angleLines.color = angleLinesColor;
                                chartInstance.options.scales.r.pointLabels.color = labelColor;

                                // Redraw chart
                                chartInstance.update();
                            };

                            // Set initial colors
                            updateColors();

                            // Listen for Filament theme change event
                            window.addEventListener('theme-changed', (event) => {
                                console.log('Theme changed event received:', event.detail);
                                updateColors(event.detail);
                            });
                        }
                    }"
                ></canvas>
            </div>

            <div>
                <table class="w-full border-collapse">
                    <tbody>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <td class="py-2 font-medium text-zinc-700 dark:text-zinc-300">HP</td>
                            <td class="py-2 text-right text-zinc-900 dark:text-white">{{ $record->hpStat ?? 0 }}</td>
                        </tr>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <td class="py-2 font-medium text-zinc-700 dark:text-zinc-300">ATK</td>
                            <td class="py-2 text-right text-zinc-900 dark:text-white">{{ $record->attackStat ?? 0 }}</td>
                        </tr>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <td class="py-2 font-medium text-zinc-700 dark:text-zinc-300">DEF</td>
                            <td class="py-2 text-right text-zinc-900 dark:text-white">{{ $record->defenseStat ?? 0 }}</td>
                        </tr>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <td class="py-2 font-medium text-zinc-700 dark:text-zinc-300">SPA</td>
                            <td class="py-2 text-right text-zinc-900 dark:text-white">{{ $record->specialAttackStat ?? 0 }}</td>
                        </tr>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <td class="py-2 font-medium text-zinc-700 dark:text-zinc-300">SPD</td>
                            <td class="py-2 text-right text-zinc-900 dark:text-white">{{ $record->specialDefenseStat ?? 0 }}</td>
                        </tr>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <td class="py-2 font-medium text-zinc-700 dark:text-zinc-300">SPE</td>
                            <td class="py-2 text-right text-zinc-900 dark:text-white">{{ $record->speedStat ?? 0 }}</td>
                        </tr>
                        <tr class="border-t-2 border-zinc-300 dark:border-zinc-600">
                            <td class="py-2 font-bold text-zinc-900 dark:text-white">BST</td>
                            <td class="py-2 text-right font-bold text-zinc-900 dark:text-white">{{ $record->totalBaseStat ?? 0 }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </x-filament::fieldset>
</div>

@assets
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endassets
