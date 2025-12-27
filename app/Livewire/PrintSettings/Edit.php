<?php

declare(strict_types=1);

namespace App\Livewire\PrintSettings;

use App\Models\PrintSetting;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Edit extends Component
{
    public string $electricity_rate_dkk_per_kwh = '';

    public string $wage_rate_dkk_per_hour = '';

    public string $default_avance_pct = '';

    public string $first_time_fee_dkk = '';

    public function mount(): void
    {
        $setting = PrintSetting::current();
        $this->electricity_rate_dkk_per_kwh = $setting->electricity_rate_dkk_per_kwh !== null ? (string) $setting->electricity_rate_dkk_per_kwh : '';
        $this->wage_rate_dkk_per_hour = $setting->wage_rate_dkk_per_hour !== null ? (string) $setting->wage_rate_dkk_per_hour : '';
        $this->default_avance_pct = $setting->default_avance_pct !== null ? (string) $setting->default_avance_pct : '';
        $this->first_time_fee_dkk = $setting->first_time_fee_dkk !== null ? (string) $setting->first_time_fee_dkk : '';
    }

    public function save(): void
    {
        $this->validate([
            'electricity_rate_dkk_per_kwh' => 'nullable|numeric|min:0',
            'wage_rate_dkk_per_hour' => 'nullable|numeric|min:0',
            'default_avance_pct' => 'nullable|numeric|min:0',
            'first_time_fee_dkk' => 'nullable|numeric|min:0',
        ]);

        $setting = PrintSetting::current();
        $setting->update([
            'electricity_rate_dkk_per_kwh' => $this->electricity_rate_dkk_per_kwh !== '' ? $this->electricity_rate_dkk_per_kwh : null,
            'wage_rate_dkk_per_hour' => $this->wage_rate_dkk_per_hour !== '' ? $this->wage_rate_dkk_per_hour : null,
            'default_avance_pct' => $this->default_avance_pct !== '' ? $this->default_avance_pct : null,
            'first_time_fee_dkk' => $this->first_time_fee_dkk !== '' ? $this->first_time_fee_dkk : null,
        ]);

        // Clear cache (handled by model booted method, but we'll ensure it's cleared)
        PrintSetting::clearCache();

        \session()->flash('success', 'Settings updated successfully.');
    }

    public function render(): View
    {
        return \view('livewire.print-settings.edit');
    }
}
