<?php

namespace App\Livewire\Settings;

use App\Models\Settings;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Settings')]
class Index extends Component
{
    public $currency_code = 'PHP';

    public $currency_symbol = '₱';

    public $savings_rate = 20;

    public $savings_rounding = 100;

    public $emergency_fund_months = 3;

    public function mount(): void
    {
        $settings = Settings::current();
        $this->currency_code = $settings->currency_code;
        $this->currency_symbol = $settings->currency_symbol;
        $this->savings_rate = (float) $settings->savings_rate;
        $this->savings_rounding = (int) $settings->savings_rounding;
        $this->emergency_fund_months = (int) $settings->emergency_fund_months;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'currency_code' => ['required', 'string', 'size:3'],
            'currency_symbol' => ['required', 'string', 'max:8'],
            'savings_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'savings_rounding' => ['required', 'integer', 'min:0', 'max:10000'],
            'emergency_fund_months' => ['required', 'integer', 'min:1', 'max:24'],
        ]);

        Settings::current()->update($validated);

        $this->dispatch('toast', message: 'Settings saved.');
    }

    public function backupNow(): void
    {
        try {
            Artisan::call('budget:backup');
            $this->dispatch('toast', message: 'Backup written to storage/app/backups.');
        } catch (\Throwable $exception) {
            $this->dispatch('toast', message: 'Backup failed: '.$exception->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.settings.index');
    }
}
