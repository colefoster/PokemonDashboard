<div style="width: 100%; overflow: hidden;">
    <x-filament::fieldset>
        <x-slot name="label">
            Base Stats
        </x-slot>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: center;">
            <div style="position: relative; height: 250px; width: 100%; overflow: hidden;">
                <canvas
                    style="max-width: 100%; height: 100%;"
                    x-data="{
                        chart: null,
                        init() {
                            this.chart = new Chart(this.$el, {
                                type: 'radar',
                                data: @js($this->getChartData()),
                                options: @js($this->getChartOptions())
                            });
                        }
                    }"
                ></canvas>
            </div>

            <div>
                <table style="width: 100%; border-collapse: collapse;">
                    <tbody>
                        <tr style="border-bottom: 1px solid rgb(229, 231, 235);">
                            <td style="padding: 0.5rem 0; font-weight: 500;">HP</td>
                            <td style="padding: 0.5rem 0; text-align: right;">{{ $record->hpStat ?? 0 }}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid rgb(229, 231, 235);">
                            <td style="padding: 0.5rem 0; font-weight: 500;">ATK</td>
                            <td style="padding: 0.5rem 0; text-align: right;">{{ $record->attackStat ?? 0 }}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid rgb(229, 231, 235);">
                            <td style="padding: 0.5rem 0; font-weight: 500;">DEF</td>
                            <td style="padding: 0.5rem 0; text-align: right;">{{ $record->defenseStat ?? 0 }}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid rgb(229, 231, 235);">
                            <td style="padding: 0.5rem 0; font-weight: 500;">SPA</td>
                            <td style="padding: 0.5rem 0; text-align: right;">{{ $record->specialAttackStat ?? 0 }}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid rgb(229, 231, 235);">
                            <td style="padding: 0.5rem 0; font-weight: 500;">SPD</td>
                            <td style="padding: 0.5rem 0; text-align: right;">{{ $record->specialDefenseStat ?? 0 }}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid rgb(229, 231, 235);">
                            <td style="padding: 0.5rem 0; font-weight: 500;">SPE</td>
                            <td style="padding: 0.5rem 0; text-align: right;">{{ $record->speedStat ?? 0 }}</td>
                        </tr>
                        <tr style="border-top: 2px solid rgb(229, 231, 235);">
                            <td style="padding: 0.5rem 0; font-weight: 700;">BST</td>
                            <td style="padding: 0.5rem 0; text-align: right; font-weight: 700;">{{ $record->totalBaseStat ?? 0 }}</td>
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