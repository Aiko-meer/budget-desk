<?php

namespace App\Livewire\Bills;

use App\Models\Account;
use App\Models\Bill;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Settings;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Bills')]
class Index extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public $name = '';

    public $amount = '';

    public $category_id = '';

    public $account_id = '';

    public $due_day = 1;

    public $notes = '';

    public $is_active = true;

    public bool $showPayment = false;

    public ?int $paymentBillId = null;

    public $payment_amount = '';

    public $payment_spent_on = '';

    public $payment_account_id = '';

    public function mount(): void
    {
        $this->category_id = Category::query()->where('is_active', true)->orderBy('sort_order')->value('id') ?? '';
        $this->account_id = Account::query()->where('is_active', true)->orderBy('sort_order')->value('id') ?? '';
        $this->payment_account_id = $this->account_id;
    }

    #[On('expense-saved')]
    public function refreshList(): void
    {
    }

    public function openCreate(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->name = '';
        $this->amount = '';
        $this->due_day = 1;
        $this->notes = '';
        $this->is_active = true;
        $this->category_id = Category::query()->where('is_active', true)->orderBy('sort_order')->value('id') ?? '';
        $this->account_id = Account::query()->where('is_active', true)->orderBy('sort_order')->value('id') ?? '';
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $bill = Bill::findOrFail($id);
        $this->resetValidation();
        $this->editingId = $bill->id;
        $this->name = $bill->name;
        $this->amount = (float) $bill->amount;
        $this->category_id = $bill->category_id;
        $this->account_id = $bill->account_id;
        $this->due_day = $bill->due_day;
        $this->notes = $bill->notes;
        $this->is_active = $bill->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'category_id' => ['required', 'exists:categories,id'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'due_day' => ['required', 'integer', 'min:1', 'max:31'],
            'notes' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $validated['notes'] = $validated['notes'] ?: null;

        if ($this->editingId) {
            Bill::findOrFail($this->editingId)->update($validated);
            $message = 'Bill updated.';
        } else {
            Bill::create($validated);
            $message = 'Bill added.';
        }

        $this->showForm = false;
        $this->dispatch('toast', message: $message);
    }

    public function toggleActive(int $id): void
    {
        $bill = Bill::findOrFail($id);
        $bill->update(['is_active' => ! $bill->is_active]);
        $this->dispatch('toast', message: $bill->name.($bill->is_active ? ' activated.' : ' paused.'));
    }

    public function delete(int $id): void
    {
        Bill::findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Bill deleted. Past payments were kept.');
    }

    public function openPayment(int $id): void
    {
        $bill = Bill::findOrFail($id);
        $this->paymentBillId = $bill->id;
        $this->payment_amount = (float) $bill->amount;
        $this->payment_spent_on = $bill->nextDueDate()->toDateString();
        $this->payment_account_id = $bill->account_id ?: $this->account_id;
        $this->showPayment = true;
    }

    public function savePayment(): void
    {
        $bill = Bill::findOrFail($this->paymentBillId);

        $validated = $this->validate([
            'payment_amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'payment_spent_on' => ['required', 'date'],
            'payment_account_id' => ['required', 'exists:accounts,id'],
        ]);

        Expense::create([
            'spent_on' => $validated['payment_spent_on'],
            'amount' => $validated['payment_amount'],
            'category_id' => $bill->category_id,
            'account_id' => $validated['payment_account_id'],
            'bill_id' => $bill->id,
            'note' => $bill->name.' payment',
        ]);

        $this->showPayment = false;
        $this->dispatch('expense-saved');
        $this->dispatch('toast', message: $bill->name.' marked as paid.');

        if ((float) $validated['payment_amount'] > (float) $bill->amount) {
            $this->dispatch('toast', message: 'Paid '.Settings::current()->currency_symbol.number_format((float) $validated['payment_amount'], 2).' — higher than the expected bill.', type: 'info');
        }
    }

    public function render()
    {
        $bills = Bill::query()->with(['category', 'account'])->orderBy('due_day')->get();

        $rows = $bills->map(function (Bill $bill) {
            $nextDue = $bill->nextDueDate();

            return [
                'bill' => $bill,
                'next_due' => $nextDue,
                'paid_this_month' => $bill->isPaidForMonth(now()),
                'days' => (int) now()->startOfDay()->diffInDays($nextDue, false),
            ];
        })->sortBy('next_due')->values();

        return view('livewire.bills.index', [
            'rows' => $rows,
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'accounts' => Account::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'activeMonthly' => round((float) Bill::query()->where('is_active', true)->sum('amount'), 2),
            'paidThisMonth' => round((float) Expense::query()
                ->whereNotNull('bill_id')
                ->whereYear('spent_on', now()->year)
                ->whereMonth('spent_on', now()->month)
                ->sum('amount'), 2),
        ]);
    }
}
