@php
    $symbol = \App\Models\Settings::current()->currency_symbol;
    $savingsRate = $summary['income'] > 0 ? round(($summary['saved'] / $summary['income']) * 100, 1) : 0;
@endphp

<div>
    <div class="rise mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-500">The numbers</p>
            <h1 class="page-title mt-1">Reports</h1>
            <p class="muted mt-1">Income, spending, and savings for {{ $monthLabel }}.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex items-center gap-1 rounded-xl border border-slate-200 bg-white/70 p-1 dark:border-white/10 dark:bg-white/[0.04]">
                <button wire:click="previousMonth" class="btn-icon h-8 w-8" aria-label="Previous month">
                    <x-icon name="chevron-left" class="h-4 w-4" />
                </button>
                <span class="min-w-[7.5rem] text-center text-xs font-bold text-slate-700 dark:text-slate-200">{{ $monthLabel }}</span>
                <button wire:click="nextMonth" class="btn-icon h-8 w-8" aria-label="Next month">
                    <x-icon name="chevron-right" class="h-4 w-4" />
                </button>
            </div>
            <a href="{{ route('reports.export', ['month' => $month]) }}" class="btn-ghost px-3 py-2 text-xs">
                <x-icon name="download" class="h-4 w-4" /> CSV
            </a>
        </div>
    </div>

    {{-- Summary --}}
    <div class="stagger mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="card p-4">
            <div class="flex items-center justify-between">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Income</p>
                <x-icon name="paydays" class="h-4 w-4 text-emerald-300" />
            </div>
            <p class="mt-2 text-xl font-extrabold tnum text-slate-900 dark:text-white" data-countup="{{ $summary['income'] }}" data-prefix="{{ $symbol }}" data-decimals="0">{{ \App\Support\Money::format($summary['income'], true, 0) }}</p>
            <p class="mt-1 text-[11px] text-slate-400">net salary received</p>
        </div>
        <div class="card p-4">
            <div class="flex items-center justify-between">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Expenses</p>
                <x-icon name="expenses" class="h-4 w-4 text-rose-300" />
            </div>
            <p class="mt-2 text-xl font-extrabold tnum text-slate-900 dark:text-white" data-countup="{{ $summary['expenses'] }}" data-prefix="{{ $symbol }}" data-decimals="0">{{ \App\Support\Money::format($summary['expenses'], true, 0) }}</p>
            <p class="mt-1 text-[11px] text-slate-400">of {{ \App\Support\Money::format($budgetTotal, true, 0) }} budgeted</p>
        </div>
        <div class="card p-4">
            <div class="flex items-center justify-between">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Saved</p>
                <x-icon name="savings" class="h-4 w-4 text-emerald-400" />
            </div>
            <p class="mt-2 text-xl font-extrabold tnum text-emerald-500" data-countup="{{ $summary['saved'] }}" data-prefix="{{ $symbol }}" data-decimals="0">{{ \App\Support\Money::format($summary['saved'], true, 0) }}</p>
            <p class="mt-1 text-[11px] text-slate-400">{{ $savingsRate }}% of income</p>
        </div>
        <div class="card p-4">
            <div class="flex items-center justify-between">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Unallocated</p>
                <x-icon name="wallet" class="h-4 w-4 text-cyan-300" />
            </div>
            <p class="mt-2 text-xl font-extrabold tnum {{ $summary['net'] < 0 ? 'text-rose-500' : 'text-slate-900 dark:text-white' }}" data-countup="{{ $summary['net'] }}" data-prefix="{{ $symbol }}" data-decimals="0">{{ \App\Support\Money::format($summary['net'], true, 0) }}</p>
            <p class="mt-1 text-[11px] text-slate-400">income − spent − saved</p>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        {{-- Cashflow --}}
        <section class="card rise p-5 sm:p-6">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Cashflow</h2>
                    <p class="text-xs text-slate-400">Six-month income, spending, and savings</p>
                </div>
                <span class="chip bg-slate-400/10 text-slate-500 dark:text-slate-300">Monthly</span>
            </div>
            <div class="h-[260px]"><canvas id="reportCashflowChart"></canvas></div>
        </section>

        {{-- Savings trend --}}
        <section class="card rise p-5 sm:p-6">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Savings trend</h2>
                    <p class="text-xs text-slate-400">Cumulative savings added, last six months</p>
                </div>
                <span class="chip bg-emerald-400/10 text-emerald-600 dark:text-emerald-300">{{ $savingsRate }}% rate</span>
            </div>
            <div class="h-[260px]"><canvas id="reportSavingsChart"></canvas></div>
        </section>

        {{-- Where money went --}}
        <section class="card rise p-5 sm:p-6">
            <div class="mb-4">
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Where money went</h2>
                <p class="text-xs text-slate-400">{{ $monthLabel }} · {{ count($categories) }} categories</p>
            </div>

            @if (count($categories))
                <div class="space-y-3.5">
                    @foreach ($categories as $row)
                        @php $share = $expenseTotal > 0 ? round(($row['total'] / $expenseTotal) * 100, 1) : 0; @endphp
                        <div>
                            <div class="mb-1.5 flex items-center gap-2 text-xs">
                                <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $row['color'] }}"></span>
                                <span class="min-w-0 flex-1 truncate font-medium text-slate-600 dark:text-slate-300">{{ $row['name'] }}</span>
                                <span class="font-semibold tnum text-slate-800 dark:text-slate-100">{{ \App\Support\Money::format($row['total']) }}</span>
                                <span class="w-10 text-right text-[10px] text-slate-400">{{ $share }}%</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="--w: {{ $share }}%; background: {{ $row['color'] }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="py-8 text-center text-sm text-slate-400">No expenses recorded for {{ $monthLabel }}.</p>
            @endif
        </section>

        {{-- Budget performance --}}
        <section class="card rise p-5 sm:p-6">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Budget performance</h2>
                    <p class="text-xs text-slate-400">{{ \App\Support\Money::format($budgetSpent) }} of {{ \App\Support\Money::format($budgetTotal) }}</p>
                </div>
                <a href="{{ route('budgets') }}" wire:navigate class="text-xs font-semibold text-emerald-600 hover:text-emerald-500 dark:text-emerald-400">Edit →</a>
            </div>

            <div class="max-h-[300px] space-y-3 overflow-y-auto pr-1">
                @forelse ($budgetRows as $row)
                    @php
                        $budget = $row['budget'];
                        $spent = $row['spent'];
                        $percent = $budget > 0 ? min(100, round(($spent / $budget) * 100, 1)) : 0;
                        $over = $budget > 0 && $spent > $budget;
                    @endphp
                    <div>
                        <div class="mb-1.5 flex items-center gap-2 text-xs">
                            <span class="min-w-0 flex-1 truncate font-medium text-slate-600 dark:text-slate-300">{{ $row['category']->name }}</span>
                            <span class="tnum text-slate-400">{{ \App\Support\Money::format($spent, true, 0) }} / {{ $budget > 0 ? \App\Support\Money::format($budget, true, 0) : '—' }}</span>
                            @if ($over)
                                <span class="text-[10px] font-bold text-rose-500">over</span>
                            @endif
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill {{ $over ? 'warm' : '' }}" style="--w: {{ $percent }}%; background: {{ $over ? '' : ($row['category']->color ?: '#34d399') }}"></div>
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-slate-400">No categories yet.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div id="chart-payload" class="hidden" data-charts="{{ json_encode($charts) }}"></div>
</div>
