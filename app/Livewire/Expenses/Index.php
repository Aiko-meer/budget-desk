<?php

namespace App\Livewire\Expenses;

use App\Models\Account;
use App\Models\Category;
use App\Models\Expense;
use App\Services\PayPeriodService;
use App\Support\Money;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Expenses')]
class Index extends Component
{
    public string $month = '';

    public string $categoryFilter = '';

    public string $accountFilter = '';

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public $amount = '';

    public $category_id = '';

    public $account_id = '';

    public $spent_on = '';

    public $note = '';

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
        $this->spent_on = now()->toDateString();
    }

    #[On('expense-saved')]
    public function refreshList(): void
    {
    }

    public function openCreate(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->amount = '';
        $this->note = '';
        $this->spent_on = now()->toDateString();
        $this->category_id = Category::query()->where('is_active', true)->orderBy('sort_order')->value('id') ?? '';
        $this->account_id = Account::query()->where('is_active', true)->orderBy('sort_order')->value('id') ?? '';
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $expense = Expense::findOrFail($id);
        $this->resetValidation();
        $this->editingId = $expense->id;
        $this->amount = (float) $expense->amount;
        $this->category_id = $expense->category_id;
        $this->account_id = $expense->account_id;
        $this->spent_on = $expense->spent_on->toDateString();
        $this->note = $expense->note;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'category_id' => ['required', 'exists:categories,id'],
            'account_id' => ['required', 'exists:accounts,id'],
            'spent_on' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['note'] = $validated['note'] ?: null;

        if ($this->editingId) {
            Expense::findOrFail($this->editingId)->update($validated);
            $message = 'Expense updated.';
        } else {
            Expense::create($validated);
            $message = 'Expense added.';
        }

        $this->showForm = false;
        $this->dispatch('expense-saved');
        $this->dispatch('toast', message: $message);
    }

    public function delete(int $id): void
    {
        Expense::findOrFail($id)->delete();
        $this->dispatch('expense-saved');
        $this->dispatch('toast', message: 'Expense deleted.');
    }

    public function resetFilters(): void
    {
        $this->categoryFilter = '';
        $this->accountFilter = '';
        $this->search = '';
        $this->month = now()->format('Y-m');
    }

    public function render()
    {
        $periods = app(PayPeriodService::class);

        $query = Expense::query()->with(['category', 'account', 'bill'])->forMonth($this->month);

        if ($this->categoryFilter) {
            $query->where('category_id', $this->categoryFilter);
        }

        if ($this->accountFilter) {
            $query->where('account_id', $this->accountFilter);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('note', 'like', '%'.$this->search.'%')
                    ->orWhereHas('category', fn ($category) => $category->where('name', 'like', '%'.$this->search.'%'));
            });
        }

        $expenses = $query->orderByDesc('spent_on')->orderByDesc('id')->get();

        foreach ($expenses as $expense) {
            $expense->period_label = $periods->periodForDate($expense->spent_on)?->label() ?? '—';
        }

        return view('livewire.expenses.index', [
            'expenses' => $expenses,
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'accounts' => Account::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'total' => round((float) $expenses->sum('amount'), 2),
            'monthLabel' => \Carbon\Carbon::createFromFormat('Y-m', $this->month)->format('F Y'),
        ]);
    }
}
