<x-layout>
    <div id="success-message" data-message="{{ session('success') }}" style="display: none;"></div>
    <x-card title="{{ ucfirst('coin') }}s">
        <div class="card-table">
            <div class="form-table-container">
                <form id="filter-form" class="form-table-filter" method="GET" action="{{ route(module('getData')) }}">
                    <div class="row">
                        <x-input name="coin_code" type="text" placeholder="Search by Coin Code" :value="request('coin_code')" col="6"/>
                        <x-input name="coin_symbol" type="text" placeholder="Search by Coin Symbol" :value="request('coin_symbol')" col="6"/>
                    </div>
                    <div class="row">
                        <x-select name="perpage" :options="['10' => '10', '20' => '20', '50' => '50', '100' => '100']" :value="request('perpage', 10)" col="2" id="perpage-select"/>
                        <x-select name="filter" :options="['All Filter', 'coin_code', 'coin_symbol']" :value="request('filter', 'All Filter')" col="4"/>
                        <x-input name="search" type="text" placeholder="Enter search term" :value="request('search')" col="6"/>
                    </div>
                    <div class="form-actions">
                        <x-button type="button" class="secondary" :attributes="['onclick' => 'window.location.href=\'' . url()->current() . '\'']">Reset</x-button>
                        <x-button type="submit" class="primary">Search</x-button>
                    </div>
                </form>
                <div class="form-table-content">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="checkbox-column"><input type="checkbox" class="checkall" /></th>
                                    <th class="text-center actions">Actions</th>
                                    <x-th column="coin_code" text="Coin Code" :model="$data->first()" />
                                    <x-th column="coin_symbol" text="Coin Symbol" :model="$data->first()" />
                                    <x-th column="coin_name" text="Coin Name" :model="$data->first()" />
                                    <x-th column="coin_price_usd" text="Coin Price Usd" :model="$data->first()" />
                                    <x-th column="coin_price_idr" text="Coin Price Idr" :model="$data->first()" />
                                    <x-th column="last_analyzed_at" text="Last Analyzed At" :model="$data->first()" />
                                    <x-th column="analysis_count" text="Analysis Count" :model="$data->first()" />
                                    <x-th column="coin_analyzed_at" text="Coin Analyzed At" :model="$data->first()" />
                                    <x-th column="coin_watch" text="Coin Watch" :model="$data->first()" />
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="hide-lg">
                                    <td data-label="Check All Data" class="checkbox-column">
                                        <input type="checkbox" class="checkall" />
                                    </td>
                                </tr>
                                @forelse($data as $list)
                                    <tr>
                                        <td class="checkbox-column"><input type="checkbox" class="row-checkbox" value="{{ $list->id }}" /></td>
                                        <td data-label="Actions">
                                            <x-action-table :model="$list" />
                                        </td>
                                        <x-td field="coin_code" :model="$list" />
                                        <x-td field="coin_symbol" :model="$list" />
                                        <x-td field="coin_name" :model="$list" />
                                        <x-td field="coin_price_usd" :model="$list" />
                                        <x-td field="coin_price_idr" :model="$list" />
                                        <x-td field="last_analyzed_at" :model="$list" />
                                        <x-td field="analysis_count" :model="$list" />
                                        <x-td field="coin_analyzed_at" :model="$list" />
                                        <x-td field="coin_watch" :model="$list" />
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center">No coins found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <x-pagination :data="$data" />
            </div>
        </div>
        <x-footer type="list" />

        <form id="bulk-delete-form" method="POST" action="{{ route(module('postBulkDelete')) }}" style="display: none;">
            @csrf
            <input type="hidden" name="ids" id="bulk-delete-ids">
        </form>
    </x-card>

</x-layout>