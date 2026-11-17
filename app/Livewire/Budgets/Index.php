<?php

namespace App\Livewire\Budgets;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Expense;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Budgets')]
class Index extends Component
{
    public string $month = '';

    public array $amounts = [];

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
        $this->loadAmounts();
    }

    public function updatedMonth(): void
    {
        $this->loadAmounts();
    }

    #[On('expense-saved')]
    public function refreshList(): void
    {
    }

    public function loadAmounts(): void
    {
        $existing = Budget::query()->where('month', $this->month)->pluck('amount', 'category_id');

        $this->amounts = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Category $category) => [
                (string) $category->id => (float) ($existing[$category->id] ?? $category->default_monthly_budget ?? 0),
            ])
            ->all();
    }

    public function save(): void
    {
        $this->validate([
            'amounts.*' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ]);

        foreach ($this->amounts as $categoryId => $amount) {
            Budget::updateOrCreate(
                ['category_id' => $categoryId, 'month' => $this->month],
                ['amount' => $amount ?: 0]
            );
        }

        $this->dispatch('toast', message: 'Budgets saved for '.$this->monthLabel().'.');
    }

    public function copyPreviousMonth(): void
    {
        $previous = Carbon::createFromFormat('Y-m', $this->month)->subMonthNoOverflow()->format('Y-m');
        $existing = Budget::query()->where('month', $previous)->pluck('amount', 'category_id');

        if ($existing->isEmpty()) {
            $this->dispatch('toast', message: 'No budgets found in '.$previous.'.', type: 'error');

            return;
        }

        foreach (Category::query()->get() as $category) {
            if (isset($existing[$category->id])) {
                $this->amounts[(string) $category->id] = (float) $existing[$category->id];
            }
        }

        $this->save();
        $this->dispatch('toast', message: 'Copied budgets from '.$previous.'.');
    }

    public function applyDefaults(): void
    {
        foreach (Category::query()->get() as $category) {
            $this->amounts[(string) $category->id] = (float) ($category->default_monthly_budget ?? 0);
        }

        $this->dispatch('toast', message: 'Default budgets applied — review and save.');
    }

    public function monthLabel(): string
    {
        return Carbon::createFromFormat('Y-m', $this->month)->format('F Y');
    }

    public function previousMonth(): void
    {
        $this->month = Carbon::createFromFormat('Y-m', $this->month)->subMonthNoOverflow()->format('Y-m');
        $this->loadAmounts();
    }

    public function nextMonth(): void
    {
        $this->month = Carbon::createFromFormat('Y-m', $this->month)->addMonthNoOverflow()->format('Y-m');
        $this->loadAmounts();
    }

    public function render()
    {
        $start = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();

        $spent = Expense::query()
            ->whereYear('spent_on', $start->year)
            ->whereMonth('spent_on', $start->month)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $categories = Category::query()->orderBy('sort_order')->orderBy('name')->get();

        $rows = $categories->map(fn (Category $category) => [
            'category' => $category,
            'budget' => (float) ($this->amounts[(string) $category->id] ?? 0),
            'spent' => (float) ($spent[$category->id] ?? 0),
        ]);

        return view('livewire.budgets.index', [
            'rows' => $rows,
            'categories' => $categories,
            'totalBudget' => round($rows->sum('budget'), 2),
            'totalSpent' => round($rows->sum('spent'), 2),
            'monthLabel' => $this->monthLabel(),
        ]);
    }
}
