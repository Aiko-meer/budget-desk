<?php

namespace App\Livewire\Paydays;

use App\Models\Account;
use App\Models\Payday;
use App\Services\PayPeriodService;
use App\Services\SavingsAdvisor;
use App\Support\Money;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Paydays')]
class Index extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public $pay_date = '';

    public $gross_amount = '';

    public $net_amount = '';

    public $account_id = '';

    public $notes = '';

    public function mount(): void
    {
        $this->pay_date = now()->toDateString();
        $this->account_id = Account::query()->where('is_active', true)->orderBy('sort_order')->value('id') ?? '';
    }

    #[On('payday-saved')]
    public function refreshList(): void
    {
    }

    public function openCreate(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->pay_date = now()->toDateString();
        $this->gross_amount = '';
        $this->net_amount = '';
        $this->notes = '';
        $this->account_id = Account::query()->where('is_active', true)->orderBy('sort_order')->value('id') ?? '';
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $payday = Payday::findOrFail($id);
        $this->resetValidation();
        $this->editingId = $payday->id;
        $this->pay_date = $payday->pay_date->toDateString();
        $this->gross_amount = $payday->gross_amount !== null ? (float) $payday->gross_amount : '';
        $this->net_amount = (float) $payday->net_amount;
        $this->account_id = $payday->account_id;
        $this->notes = $payday->notes;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'pay_date' => ['required', 'date', Rule::unique('paydays', 'pay_date')->ignore($this->editingId)],
            'gross_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'net_amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'account_id' => ['required', 'exists:accounts,id'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['gross_amount'] = $validated['gross_amount'] === '' ? null : $validated['gross_amount'];
        $validated['notes'] = $validated['notes'] ?: null;

        if ($this->editingId) {
            Payday::findOrFail($this->editingId)->update($validated);
            $message = 'Payday updated.';
        } else {
            Payday::create($validated);
            $message = 'Payday logged.';
        }

        $this->showForm = false;
        $this->dispatch('payday-saved');
        $this->dispatch('toast', message: $message);
    }

    public function delete(int $id): void
    {
        Payday::findOrFail($id)->delete();
        $this->dispatch('payday-saved');
        $this->dispatch('toast', message: 'Payday deleted. Expenses and transfers were kept.');
    }

    public function render()
    {
        $periods = app(PayPeriodService::class);
        $advisor = app(SavingsAdvisor::class);

        $paydays = Payday::query()->with('account')->orderByDesc('pay_date')->limit(24)->get();

        $rows = $paydays->map(function (Payday $payday) use ($periods, $advisor) {
            $period = $periods->periodForPayday($payday);
            $advice = $advisor->advise($payday);

            return [
                'payday' => $payday,
                'period' => $period,
                'advice' => $advice,
            ];
        });

        $thisYear = Payday::query()->whereYear('pay_date', now()->year)->sum('net_amount');
        $average = Payday::query()->avg('net_amount');

        return view('livewire.paydays.index', [
            'rows' => $rows,
            'accounts' => Account::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'yearIncome' => round((float) $thisYear, 2),
            'average' => round((float) $average, 2),
            'nextPayday' => $periods->nextPaydayDate(),
        ]);
    }
}
