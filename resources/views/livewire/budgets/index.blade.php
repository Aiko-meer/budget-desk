@php $symbol = \App\Models\Settings::current()->currency_symbol; @endphp

<div>
    <div class="rise mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-500">Monthly plan</p>
            <h1 class="page-title mt-1">Budgets</h1>
            <p class="muted mt-1">Set a limit per category and watch the month stay honest.</p>
        </div>
        <div class="flex items-center gap-2 rounded-xl border border-slate-200/70 bg-white/60 p-1 dark:border-white/10 dark:bg-white/5">
            <button class="btn-icon h-8 w-8 border-0 bg-transparent dark:bg-transparent" wire:click="previousMonth" aria-label="Previous month">
                <x-icon name="chevron-left" class="h-4 w-4" />
            </button>
            <span class="min-w-[9rem] text-center text-sm font-bold text-slate-800 dark:text-slate-100">{{ $monthLabel }}</span>
            <button class="btn-icon h-8 w-8 border-0 bg-transparent dark:bg-transparent" wire:click="nextMonth" aria-label="Next month">
                <x-icon name="chevron-right" class="h-4 w-4" />
            </button>
        </div>
    </div>

    <div class="stagger mb-5 grid gap-3 sm:grid-cols-3">
        <div class="card p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Budgeted</p>
            <p class="mt-2 text-xl font-extrabold tnum text-slate-900 dark:text-white">{{ \App\Support\Money::format($totalBudget, true, 0) }}</p>
        </div>
        <div class="card p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Spent</p>
            <p class="mt-2 text-xl font-extrabold tnum text-slate-900 dark:text-white">{{ \App\Support\Money::format($totalSpent, true, 0) }}</p>
        </div>
        <div class="card p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Remaining</p>
            <p class="mt-2 text-xl font-extrabold tnum {{ $totalBudget - $totalSpent < 0 ? 'text-rose-500' : 'text-emerald-500' }}">
                {{ \App\Support\Money::format($totalBudget - $totalSpent, true, 0) }}
            </p>
        </div>
    </div>

    <section class="card rise overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 p-5 dark:border-white/[0.06]">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Category limits</h2>
                <p class="text-xs text-slate-400">Spent amounts update automatically from expenses.</p>
            </div>
            <div class="flex flex-wrap gap-1.5">
                <button wire:click="copyPreviousMonth" class="btn-ghost px-3 py-1.5 text-xs">Copy previous</button>
                <button wire:click="applyDefaults" class="btn-ghost px-3 py-1.5 text-xs">Apply defaults</button>
            </div>
        </div>

        <form wire:submit="save">
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-100 dark:border-white/[0.06]">
                        <tr>
                            <th class="table-head px-5 py-3">Category</th>
                            <th class="table-head px-5 py-3 w-40">Budget</th>
                            <th class="table-head px-5 py-3 text-right">Spent</th>
                            <th class="table-head px-5 py-3 text-right">Left</th>
                            <th class="table-head px-5 py-3 w-48">Progress</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/[0.06]">
                        @foreach ($rows as $row)
                            @php
                                $percent = $row['budget'] > 0 ? min(100, round(($row['spent'] / $row['budget']) * 100, 1)) : 0;
                                $over = $row['spent'] > $row['budget'] && $row['budget'] > 0;
                            @endphp
                            <tr class="transition hover:bg-slate-50/50 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $row['category']->color ?: '#64748b' }}"></span>
                                        <div>
                                            <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $row['category']->name }}</p>
                                            <p class="text-[10px] uppercase tracking-wider text-slate-400">{{ $row['category']->group }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <input type="number" step="0.01" min="0" wire:model="amounts.{{ $row['category']->id }}" class="input py-2 tnum">
                                    @error('amounts.'.$row['category']->id) <p class="mt-1 text-[10px] text-rose-500">{{ $message }}</p> @enderror
                                </td>
                                <td class="px-5 py-3 text-right font-semibold tnum text-slate-700 dark:text-slate-200">{{ \App\Support\Money::format($row['spent']) }}</td>
                                <td class="px-5 py-3 text-right font-semibold tnum {{ $over ? 'text-rose-500' : 'text-emerald-600 dark:text-emerald-400' }}">
                                    {{ \App\Support\Money::format($row['budget'] - $row['spent']) }}
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="progress-track h-1.5 flex-1">
                                            <div class="progress-fill {{ $over ? 'warm' : '' }}" style="--w: {{ $percent }}%"></div>
                                        </div>
                                        <span class="w-9 text-right text-[10px] font-semibold text-slate-400 tnum">{{ $percent }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 md:hidden dark:divide-white/[0.06]">
                @foreach ($rows as $row)
                    @php
                        $percent = $row['budget'] > 0 ? min(100, round(($row['spent'] / $row['budget']) * 100, 1)) : 0;
                        $over = $row['spent'] > $row['budget'] && $row['budget'] > 0;
                    @endphp
                    <div class="p-4">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $row['category']->name }}</p>
                            <p class="text-[11px] font-semibold {{ $over ? 'text-rose-500' : 'text-emerald-500' }}">{{ $percent }}%</p>
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <input type="number" step="0.01" min="0" wire:model="amounts.{{ $row['category']->id }}" class="input py-2 text-sm tnum">
                            <span class="whitespace-nowrap text-[11px] text-slate-400">spent {{ \App\Support\Money::format($row['spent'], true, 0) }}</span>
                        </div>
                        <div class="progress-track mt-2">
                            <div class="progress-fill {{ $over ? 'warm' : '' }}" style="--w: {{ $percent }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-slate-100 p-4 dark:border-white/[0.06]">
                <p class="text-[11px] text-slate-400">Changes apply to {{ $monthLabel }} only.</p>
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Save budgets</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </form>
    </section>
</div>
