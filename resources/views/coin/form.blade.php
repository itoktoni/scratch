<x-layout>
    <x-card :model="$model">
        <x-form :model="$model">
            <x-input name="coin_code" :attributes="isset($model) ? ['readonly' => true] : []" hint="Coin Code cannot be changed" required />

            <x-input name="coin_symbol"  />

            <x-input name="coin_name"  />

            <x-input type="number" name="coin_price_usd"  />

            <x-input type="number" name="coin_price_idr"  />

            <x-input name="last_analyzed_at"  />

            <x-input type="number" name="analysis_count" required />

            <x-input name="coin_analyzed_at"  />

            <x-input type="number" name="coin_watch" required />

            <x-footer :model="$model" />

        </x-form>
    </x-card>
</x-layout>