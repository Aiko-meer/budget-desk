<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Settings;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class ExpenseQuickAdd extends Component
{
    public bool $show = false;

    public $amount = '';

    public $category_id = '';

    public $account_id = '';

    public $spent_on = '';

    public $note = '';

    public function mount(): void
    {
        $this->spent_on = now()->toDateString();
        $this->category_id = Category::query()->where('is_active', true)->orderBy('sort_order')->value('id') ?? '';
        $this->account_id = Account::query()->where('is_active', true)->orderBy('sort_order')->value('id') ?? '';
    }

    #[On('open-quick-add')]
    public function open(): void
    {
        $this->resetValidation();
        $this->spent_on = now()->toDateString();
        $this->show = true;

        if (! $this->category_id) {
            $this->category_id = Category::query()->where('is_active', true)->orderBy('sort_order')->value('id') ?? '';
        }

        if (! $this->account_id) {
            $this->account_id = Account::query()->where('is_active', true)->orderBy('sort_order')->value('id') ?? '';
        }
    }

    public function close(): void
    {
        $this->show = false;
        $this->resetValidation();
    }

    public function save(): void
    {
        if (! $this->category_id || ! $this->account_id) {
            $this->dispatch('toast', message: 'Add a category and an account first.', type: 'error');

            return;
        }

        $validated = $this->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'category_id' => ['required', 'exists:categories,id'],
            'account_id' => ['required', 'exists:accounts,id'],
            'spent_on' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['note'] = $validated['note'] ?: null;

        Expense::create($validated);

        $this->dispatch('expense-saved');
        $this->dispatch('toast', message: 'Expense of '.Settings::current()->currency_symbol.number_format((float) $validated['amount'], 2).' added.');

        $this->amount = '';
        $this->note = '';
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.expense-quick-add', [
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'accounts' => Account::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }
}
