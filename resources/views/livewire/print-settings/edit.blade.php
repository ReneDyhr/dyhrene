<div>
    @section('title', 'Settings')
    @include('components.layouts.sidenav')
    <div id="main">
        @include('components.layouts.header')
        <div class="content homepage">
            <div class="col-12">
                <div class="storage-list">
                    <div class="recipe">
                        <h1>Settings</h1>

                        @if (session()->has('success'))
                            <div class="alert alert-success" style="padding: 10px; margin-bottom: 15px;">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form wire:submit.prevent="save">
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="electricity_rate_dkk_per_kwh">Electricity Rate (DKK per kWh)</label>
                                <input type="number" id="electricity_rate_dkk_per_kwh"
                                    wire:model="electricity_rate_dkk_per_kwh" class="form-control" step="0.0001" min="0"
                                    placeholder="0.0000">
                                @error('electricity_rate_dkk_per_kwh')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="wage_rate_dkk_per_hour">Wage Rate (DKK per Hour)</label>
                                <input type="number" id="wage_rate_dkk_per_hour" wire:model="wage_rate_dkk_per_hour"
                                    class="form-control" step="0.01" min="0" placeholder="0.00">
                                @error('wage_rate_dkk_per_hour')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="default_avance_pct">Default Avance (%)</label>
                                <input type="number" id="default_avance_pct" wire:model="default_avance_pct"
                                    class="form-control" step="0.01" min="0" placeholder="0.00">
                                @error('default_avance_pct')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="first_time_fee_dkk">First Time Fee (DKK)</label>
                                <input type="number" id="first_time_fee_dkk" wire:model="first_time_fee_dkk"
                                    class="form-control" step="0.01" min="0" placeholder="0.00">
                                @error('first_time_fee_dkk')
                                    <span class="text-danger" style="font-size: 0.9em;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div style="margin-top: 20px;">
                                <button type="submit" class="btn btn-primary" style="color: #fff;">Save
                                    Settings</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
</div>