<?php

namespace App\Livewire\Accounts;

use App\Models\Account;
use App\Models\SavingsGoal;
use App\Models\Transfer;
use App\Services\BalanceService;
use App\Support\Money;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Accounts')]
class Index extends Component
{
    public bool $showAccountForm = false;

    public ?int $accountId = null;

    public $name = '';

    public $institution = '';

    public $type = 'checking';

    public $counts_as_savings = false;

    public $opening_balance = 0;

    public $color = '#0f172a';

    public $is_active = true;

    public $notes = '';

    public bool $showTransferForm = false;

    public $from_account_id = '';

    public $to_account_id = '';

    public $amount = '';

    public $transferred_on = '';

    public $goal_id = '';

    public $transfer_note = '';

    public ?int $viewingAccountId = null;

    public function mount(): void
    {
        $this->transferred_on = now()->toDateString();
        $this->from_account_id = Account::query()->where('is_active', true)->orderBy('sort_order')->value('id') ?? '';
        $this->to_account_id = Account::query()->where('is_active', true)->where('counts_as_savings', true)->value('id') ?? '';
    }

    #[On('transfer-saved')]
    public function refreshList(): void
    {
    }

    public function openCreate(): void
    {
        $this->resetValidation();
        $this->accountId = null;
        $this->name = '';
        $this->institution = '';
        $this->type = 'checking';
        $this->counts_as_savings = false;
        $this->opening_balance = 0;
        $this->color = '#0f172a';
        $this->is_active = true;
        $this->notes = '';
        $this->showAccountForm = true;
    }

    public function openEdit(int $id): void
    {
        $account = Account::findOrFail($id);
        $this->resetValidation();
        $this->accountId = $account->id;
        $this->name = $account->name;
        $this->institution = $account->institution;
        $this->type = $account->type;
        $this->counts_as_savings = $account->counts_as_savings;
        $this->opening_balance = (float) $account->opening_balance;
        $this->color = $account->color ?: '#0f172a';
        $this->is_active = $account->is_active;
        $this->notes = $account->notes;
        $this->showAccountForm = true;
    }

    public function updatedType($value): void
    {
        if (in_array($value, ['savings', 'investment'], true)) {
            $this->counts_as_savings = true;
        }
    }

    public function saveAccount(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('accounts', 'name')->ignore($this->accountId)],
            'institution' => ['nullable', 'string', 'max:80'],
            'type' => ['required', Rule::in(['cash', 'checking', 'savings', 'e_wallet', 'investment'])],
            'counts_as_savings' => ['boolean'],
            'opening_balance' => ['required', 'numeric', 'min:-99999999', 'max:99999999'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['institution'] = $validated['institution'] ?: null;
        $validated['notes'] = $validated['notes'] ?: null;

        if ($this->accountId) {
            Account::findOrFail($this->accountId)->update($validated);
            $message = 'Account updated.';
        } else {
            Account::create($validated);
            $message = 'Account added.';
        }

        $this->showAccountForm = false;
        $this->dispatch('toast', message: $message);
    }

    public function toggleActive(int $id): void
    {
        $account = Account::findOrFail($id);
        $account->update(['is_active' => ! $account->is_active]);
        $this->dispatch('toast', message: $account->name.($account->is_active ? ' activated.' : ' archived.'));
    }

    public function deleteAccount(int $id): void
    {
        $account = Account::findOrFail($id);

        try {
            $account->delete();
            $this->dispatch('toast', message: 'Account deleted.');
        } catch (\Throwable $exception) {
            $this->dispatch('toast', message: 'This account has transactions. Archive it instead.', type: 'error');
        }
    }

    public function openTransfer(): void
    {
        $this->resetValidation();
        $this->amount = '';
        $this->transfer_note = '';
        $this->transferred_on = now()->toDateString();
        $this->goal_id = '';
        $this->from_account_id = Account::query()->where('is_active', true)->orderBy('sort_order')->value('id') ?? '';
        $this->to_account_id = Account::query()->where('is_active', true)->where('counts_as_savings', true)->value('id') ?? '';
        $this->showTransferForm = true;
    }

    public function saveTransfer(): void
    {
        $validated = $this->validate([
            'from_account_id' => ['required', 'exists:accounts,id'],
            'to_account_id' => ['required', 'exists:accounts,id', 'different:from_account_id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'transferred_on' => ['required', 'date', 'before_or_equal:today'],
            'goal_id' => ['nullable', 'exists:savings_goals,id'],
            'transfer_note' => ['nullable', 'string', 'max:255'],
        ]);

        $goal = $validated['goal_id'] ? SavingsGoal::find($validated['goal_id']) : null;
        $before = $goal?->progressAmount() ?? 0;
        $target = $goal?->effectiveTarget() ?? 0;

        Transfer::create([
            'from_account_id' => $validated['from_account_id'],
            'to_account_id' => $validated['to_account_id'],
            'amount' => $validated['amount'],
            'transferred_on' => $validated['transferred_on'],
            'goal_id' => $validated['goal_id'] ?: null,
            'note' => $validated['transfer_note'] ?: null,
        ]);

        $this->showTransferForm = false;
        $this->dispatch('transfer-saved');
        $this->dispatch('toast', message: 'Transfer recorded.');

        if ($goal && $target > 0 && $before < $target && $goal->fresh()->progressAmount() >= $target) {
            $this->dispatch('celebrate');
            $this->dispatch('toast', message: $goal->name.' goal reached!');
        }

        $source = Account::find($validated['from_account_id']);
        if ($source && app(BalanceService::class)->balance($source) < 0) {
            $this->dispatch('toast', message: 'Heads up: '.$source->name.' is now negative. Check pending deposits.', type: 'info');
        }
    }

    public function deleteTransfer(int $id): void
    {
        Transfer::findOrFail($id)->delete();
        $this->dispatch('transfer-saved');
        $this->dispatch('toast', message: 'Transfer deleted.');
    }

    public function viewLedger(int $id): void
    {
        $this->viewingAccountId = $id;
    }

    public function closeLedger(): void
    {
        $this->viewingAccountId = null;
    }

    public function render()
    {
        $balanceService = app(BalanceService::class);
        $balances = $balanceService->all();

        $accounts = Account::query()
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $transfers = Transfer::query()
            ->with(['fromAccount', 'toAccount', 'goal'])
            ->orderByDesc('transferred_on')
            ->orderByDesc('id')
            ->limit(12)
            ->get();

        $viewing = $this->viewingAccountId ? Account::find($this->viewingAccountId) : null;

        return view('livewire.accounts.index', [
            'accounts' => $accounts,
            'balances' => $balances,
            'totalCash' => $balanceService->totalCash(),
            'totalSavings' => $balanceService->totalSavings(),
            'transfers' => $transfers,
            'goals' => SavingsGoal::query()->where('is_active', true)->orderBy('name')->get(),
            'viewing' => $viewing,
            'ledger' => $viewing ? $balanceService->ledger($viewing) : collect(),
        ]);
    }
}
