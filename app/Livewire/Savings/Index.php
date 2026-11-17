<?php

namespace App\Livewire\Savings;

use App\Models\Account;
use App\Models\SavingsGoal;
use App\Models\Settings;
use App\Models\Transfer;
use App\Services\BalanceService;
use App\Services\PayPeriodService;
use App\Services\ReportService;
use App\Services\SavingsAdvisor;
use App\Support\Money;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Savings')]
class Index extends Component
{
    public bool $showGoalForm = false;

    public ?int $goalId = null;

    public $name = '';

    public $target_amount = '';

    public $target_date = '';

    public $is_emergency_fund = false;

    public $color = '#34d399';

    public $goal_notes = '';

    public $is_active = true;

    public bool $showSaveForm = false;

    public $amount = '';

    public $from_account_id = '';

    public $to_account_id = '';

    public $goal_id = '';

    public $transferred_on = '';

    public $note = '';

    public function mount(): void
    {
        $this->transferred_on = now()->toDateString();
        $this->from_account_id = Account::query()->where('is_active', true)->where('counts_as_savings', false)->orderBy('sort_order')->value('id') ?? '';
        $this->to_account_id = Account::query()->where('is_active', true)->where('counts_as_savings', true)->orderBy('sort_order')->value('id') ?? '';
    }

    #[On('transfer-saved')]
    public function refreshList(): void
    {
    }

    public function openGoalCreate(): void
    {
        $this->resetValidation();
        $this->goalId = null;
        $this->name = '';
        $this->target_amount = '';
        $this->target_date = '';
        $this->is_emergency_fund = false;
        $this->color = '#34d399';
        $this->goal_notes = '';
        $this->is_active = true;
        $this->showGoalForm = true;
    }

    public function openGoalEdit(int $id): void
    {
        $goal = SavingsGoal::findOrFail($id);
        $this->resetValidation();
        $this->goalId = $goal->id;
        $this->name = $goal->name;
        $this->target_amount = $goal->target_amount !== null ? (float) $goal->target_amount : '';
        $this->target_date = $goal->target_date?->toDateString() ?? '';
        $this->is_emergency_fund = $goal->is_emergency_fund;
        $this->color = $goal->color ?: '#34d399';
        $this->goal_notes = $goal->notes;
        $this->is_active = $goal->is_active;
        $this->showGoalForm = true;
    }

    public function saveGoal(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'target_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'target_date' => ['nullable', 'date'],
            'is_emergency_fund' => ['boolean'],
            'color' => ['nullable', 'string', 'max:20'],
            'goal_notes' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        if ($validated['is_emergency_fund']) {
            SavingsGoal::query()
                ->where('is_emergency_fund', true)
                ->when($this->goalId, fn ($query) => $query->where('id', '!=', $this->goalId))
                ->update(['is_emergency_fund' => false]);
        }

        $payload = [
            'name' => $validated['name'],
            'target_amount' => $validated['target_amount'] !== '' ? $validated['target_amount'] : null,
            'target_date' => $validated['target_date'] ?: null,
            'is_emergency_fund' => $validated['is_emergency_fund'],
            'color' => $validated['color'] ?: '#34d399',
            'notes' => $validated['goal_notes'] ?: null,
            'is_active' => $validated['is_active'],
        ];

        if ($this->goalId) {
            SavingsGoal::findOrFail($this->goalId)->update($payload);
            $message = 'Goal updated.';
        } else {
            SavingsGoal::create($payload);
            $message = 'Goal created.';
        }

        $this->showGoalForm = false;
        $this->dispatch('toast', message: $message);
    }

    public function toggleGoal(int $id): void
    {
        $goal = SavingsGoal::findOrFail($id);
        $goal->update(['is_active' => ! $goal->is_active]);
        $this->dispatch('toast', message: $goal->name.($goal->is_active ? ' reactivated.' : ' archived.'));
    }

    public function openSave(): void
    {
        $savingsAccount = Account::query()->where('is_active', true)->where('counts_as_savings', true)->first();

        if (! $savingsAccount) {
            $this->dispatch('toast', message: 'Create a savings account first in Accounts.', type: 'error');

            return;
        }

        $this->resetValidation();
        $this->transferred_on = now()->toDateString();
        $this->from_account_id = Account::query()->where('is_active', true)->where('counts_as_savings', false)->orderBy('sort_order')->value('id') ?? '';
        $this->to_account_id = $savingsAccount->id;
        $this->goal_id = SavingsGoal::query()->where('is_active', true)->where('is_emergency_fund', true)->value('id') ?? '';
        $this->amount = '';
        $this->note = '';

        $period = app(PayPeriodService::class)->currentPeriod();
        if ($period?->payday) {
            $advice = app(SavingsAdvisor::class)->advise($period->payday);
            $this->amount = $advice['target'];
        }

        $this->showSaveForm = true;
    }

    public function saveTransfer(): void
    {
        $validated = $this->validate([
            'from_account_id' => ['required', 'exists:accounts,id'],
            'to_account_id' => ['required', 'exists:accounts,id', 'different:from_account_id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'transferred_on' => ['required', 'date', 'before_or_equal:today'],
            'goal_id' => ['nullable', 'exists:savings_goals,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $destination = Account::findOrFail($validated['to_account_id']);

        if (! $destination->counts_as_savings) {
            $this->addError('to_account_id', 'Choose an account that counts as savings.');

            return;
        }

        $goal = $validated['goal_id'] ? SavingsGoal::find($validated['goal_id']) : null;
        $before = $goal?->progressAmount() ?? 0;
        $target = $goal?->effectiveTarget() ?? 0;

        Transfer::create([
            'from_account_id' => $validated['from_account_id'],
            'to_account_id' => $validated['to_account_id'],
            'amount' => $validated['amount'],
            'transferred_on' => $validated['transferred_on'],
            'goal_id' => $validated['goal_id'] ?: null,
            'note' => $validated['note'] ?: null,
        ]);

        $this->showSaveForm = false;
        $this->dispatch('transfer-saved');
        $this->dispatch('toast', message: 'Saved '.Money::format($validated['amount']).' to '.$destination->name.'.');

        if ($goal && $target > 0 && $before < $target && $goal->fresh()->progressAmount() >= $target) {
            $this->dispatch('celebrate');
            $this->dispatch('toast', message: $goal->name.' goal reached. Nice work!');
        }
    }

    public function deleteTransfer(int $id): void
    {
        Transfer::findOrFail($id)->delete();
        $this->dispatch('transfer-saved');
        $this->dispatch('toast', message: 'Savings entry deleted.');
    }

    public function render()
    {
        $balanceService = app(BalanceService::class);
        $advisor = app(SavingsAdvisor::class);
        $periods = app(PayPeriodService::class);
        $reports = app(ReportService::class);

        $currentPeriod = $periods->currentPeriod();
        $advice = $currentPeriod?->payday ? $advisor->advise($currentPeriod->payday) : null;
        $month = now()->format('Y-m');
        $summary = $reports->monthSummary($month);

        $goals = SavingsGoal::query()->orderByDesc('is_active')->orderBy('name')->get();

        $contributions = Transfer::query()
            ->with(['fromAccount', 'toAccount', 'goal'])
            ->whereHas('toAccount', fn ($query) => $query->where('counts_as_savings', true))
            ->whereHas('fromAccount', fn ($query) => $query->where('counts_as_savings', false))
            ->orderByDesc('transferred_on')
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        $paydayRows = collect($periods->lastPeriods(6))->map(function ($period) use ($advisor) {
            return $period->payday ? $advisor->advise($period->payday) : null;
        })->filter()->values();

        $emergency = $goals->firstWhere('is_emergency_fund', true);

        return view('livewire.savings.index', [
            'goals' => $goals,
            'activeGoals' => $goals->where('is_active', true),
            'contributions' => $contributions,
            'paydayRows' => $paydayRows,
            'advice' => $advice,
            'emergency' => $emergency,
            'totalSavings' => $balanceService->totalSavings(),
            'monthSaved' => $summary['saved'],
            'accounts' => Account::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'savingsAccounts' => Account::query()->where('is_active', true)->where('counts_as_savings', true)->orderBy('sort_order')->get(),
            'sourceAccounts' => Account::query()->where('is_active', true)->where('counts_as_savings', false)->orderBy('sort_order')->get(),
            'symbol' => Settings::current()->currency_symbol,
        ]);
    }
}
