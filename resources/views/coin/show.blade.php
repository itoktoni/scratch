<x-layout>
    <x-card :model="$model">
        <x-input name="coin_code" :model="$model" readonly />
            <x-input name="coin_symbol" :model="$model" readonly />
            <x-input name="coin_name" :model="$model" readonly />
            <x-input name="coin_price_usd" :model="$model" readonly />
            <x-input name="coin_price_idr" :model="$model" readonly />
            <x-input name="last_analyzed_at" :model="$model" readonly />
            <x-input name="analysis_count" :model="$model" readonly />
            <x-input name="coin_analyzed_at" :model="$model" readonly />
            <x-input name="coin_watch" :model="$model" readonly />

        <x-footer type="show" :model="$model" />
    </x-card>
</x-layout>