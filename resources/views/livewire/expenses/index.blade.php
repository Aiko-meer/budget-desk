@php $symbol = \App\Models\Settings::current()->currency_symbol; @endphp

<div>
    <div class="rise mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-500">Daily spending</p>
            <h1 class="page-title mt-1">Expenses</h1>
            <p class="muted mt-1">Every peso out, sorted by the payday cycle it belongs to.</p>
        </div>
        <button type="button" wire:click="openCreate" class="btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> Add expense
        </button>
    </div>

    <section class="card rise mb-5 p-4 sm:p-5">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label class="label" for="filter-month">Month</label>
                <input id="filter-month" type="month" wire:model.live="month" class="input">
            </div>
            <div>
                <label class="label" for="filter-category">Category</label>
                <select id="filter-category" wire:model.live="categoryFilter" class="input">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="filter-account">Account</label>
                <select id="filter-account" wire:model.live="accountFilter" class="input">
                    <option value="">All accounts</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="filter-search">Search note</label>
                <input id="filter-search" type="text" wire:model.live.debounce.300ms="search" class="input" placeholder="Lunch, fare...">
            </div>
            <div class="flex items-end">
                <button type="button" wire:click="resetFilters" class="btn-ghost w-full">
                    <x-icon name="x" class="h-4 w-4" /> Reset
                </button>
            </div>
        </div>
    </section>

    <section class="card rise overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5 dark:border-white/[0.06]">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ $monthLabel }}</h2>
                <p class="text-xs text-slate-400">{{ $expenses->count() }} {{ \Illuminate\Support\Str::plural('expense', $expenses->count()) }} in view</p>
            </div>
            <div class="text-right">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Total</p>
                <p class="text-xl font-extrabold tnum text-slate-900 dark:text-white">{{ \App\Support\Money::format($total) }}</p>
            </div>
        </div>

        @if ($expenses->count())
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-100 dark:border-white/[0.06]">
                        <tr>
                            <th class="table-head px-5 py-3">Date</th>
                            <th class="table-head px-5 py-3">Note</th>
                            <th class="table-head px-5 py-3">Category</th>
                            <th class="table-head px-5 py-3">Account</th>
                            <th class="table-head px-5 py-3">Pay period</th>
                            <th class="table-head px-5 py-3 text-right">Amount</th>
                            <th class="table-head px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/[0.06]">
                        @foreach ($expenses as $expense)
                            <tr class="group transition hover:bg-slate-50/60 dark:hover:bg-white/[0.02]">
                                <td class="whitespace-nowrap px-5 py-3.5 text-xs text-slate-500 dark:text-slate-400">{{ $expense->spent_on->format('M j, Y') }}</td>
                                <td class="px-5 py-3.5">
                                    <p class="max-w-[16rem] truncate font-semibold text-slate-800 dark:text-slate-100">{{ $expense->note ?: '—' }}</p>
                                    @if ($expense->bill)
                                        <p class="text-[10px] text-slate-400">Bill: {{ $expense->bill->name }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="chip border border-slate-200 bg-slate-50 text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                                        <span class="h-1.5 w-1.5 rounded-full" style="background: {{ $expense->category?->color ?: '#64748b' }}"></span>
                                        {{ $expense->category?->name }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-xs text-slate-500 dark:text-slate-400">{{ $expense->account?->name }}</td>
                                <td class="px-5 py-3.5 text-xs text-slate-400">{{ $expense->period_label }}</td>
                                <td class="px-5 py-3.5 text-right font-bold tnum text-slate-900 dark:text-white">{{ \App\Support\Money::format($expense->amount) }}</td>
                                <td class="px-5 py-3.5">
                                    <div class="flex justify-end gap-1 opacity-100 transition md:opacity-0 md:group-hover:opacity-100">
                                        <button class="btn-icon h-8 w-8" wire:click="openEdit({{ $expense->id }})" aria-label="Edit">
                                            <x-icon name="pencil" class="h-3.5 w-3.5" />
                                        </button>
                                        <button class="btn-icon h-8 w-8 text-rose-500" wire:click="delete({{ $expense->id }})" wire:confirm="Delete this expense?" aria-label="Delete">
                                            <x-icon name="trash" class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 md:hidden dark:divide-white/[0.06]">
                @foreach ($expenses as $expense)
                    <div class="flex items-start gap-3 p-4">
                        <span class="grid h-9 w-9 place-items-center rounded-xl text-white" style="background: {{ $expense->category?->color ?: '#64748b' }}">
                            <x-icon name="expenses" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $expense->note ?: $expense->category?->name }}</p>
                                <p class="whitespace-nowrap text-sm font-bold tnum text-slate-900 dark:text-white">{{ \App\Support\Money::format($expense->amount) }}</p>
                            </div>
                            <p class="mt-0.5 text-[11px] text-slate-400">{{ $expense->spent_on->format('M j') }} · {{ $expense->category?->name }} · {{ $expense->account?->name }}</p>
                            <p class="text-[10px] text-slate-400">{{ $expense->period_label }}</p>
                            <div class="mt-2 flex gap-1">
                                <button class="btn-ghost px-2 py-1 text-[11px]" wire:click="openEdit({{ $expense->id }})">Edit</button>
                                <button class="btn-danger px-2 py-1 text-[11px]" wire:click="delete({{ $expense->id }})" wire:confirm="Delete this expense?">Delete</button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="grid place-items-center px-6 py-16 text-center">
                <span class="grid h-14 w-14 place-items-center rounded-2xl bg-emerald-400/10 text-emerald-500">
                    <x-icon name="expenses" class="h-7 w-7" />
                </span>
                <h3 class="mt-4 text-base font-bold text-slate-900 dark:text-white">No expenses in this view</h3>
                <p class="mt-1 max-w-sm text-sm text-slate-400">Change the filters or log your first expense for {{ $monthLabel }}.</p>
                <button wire:click="openCreate" class="btn-primary mt-4"><x-icon name="plus" class="h-4 w-4" /> Add expense</button>
            </div>
        @endif
    </section>

    @if ($showForm)
        <div class="fixed inset-0 z-[90] flex items-end justify-center p-3 sm:items-center sm:p-6">
            <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" wire:click="$set('showForm', false)"></div>
            <div class="card modal-pop relative w-full max-w-lg p-5 sm:p-6">
                <div class="mb-5 flex items-start justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-500">{{ $editingId ? 'Edit' : 'New' }}</p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ $editingId ? 'Update expense' : 'Add expense' }}</h2>
                    </div>
                    <button class="btn-icon h-8 w-8" wire:click="$set('showForm', false)" aria-label="Close">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <form wire:submit="save" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Amount</label>
                            <input type="number" step="0.01" min="0.01" wire:model="amount" class="input tnum text-lg font-bold">
                            @error('amount') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Date</label>
                            <input type="date" wire:model="spent_on" max="{{ now()->toDateString() }}" class="input">
                            @error('spent_on') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Category</label>
                            <select wire:model="category_id" class="input">
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Account</label>
                            <select wire:model="account_id" class="input">
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                            @error('account_id') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="label">Note <span class="normal-case tracking-normal text-slate-400">(optional)</span></label>
                        <input type="text" wire:model="note" class="input" placeholder="What was it for?">
                        @error('note') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button type="button" wire:click="$set('showForm', false)" class="btn-ghost flex-1">Cancel</button>
                        <button type="submit" class="btn-primary flex-1" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">{{ $editingId ? 'Save changes' : 'Add expense' }}</span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
