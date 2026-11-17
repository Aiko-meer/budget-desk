@php
    $symbol = \App\Models\Settings::current()->currency_symbol;
    $status = $advice['status'] ?? 'met';
    $statusMap = [
        'met' => ['On target', 'bg-emerald-400/15 text-emerald-300 ring-1 ring-emerald-400/30'],
        'short' => ['Short '.\App\Support\Money::format(abs($advice['difference'] ?? 0)), 'bg-rose-400/15 text-rose-300 ring-1 ring-rose-400/30'],
        'ahead' => ['Ahead '.\App\Support\Money::format(abs($advice['difference'] ?? 0)), 'bg-cyan-400/15 text-cyan-300 ring-1 ring-cyan-400/30'],
    ];
    [$statusLabel, $statusClass] = $statusMap[$status] ?? $statusMap['met'];
    $savedPercent = ($advice['target'] ?? 0) > 0 ? min(100, round((($advice['actual_saved'] ?? 0) / $advice['target']) * 100, 1)) : 0;
@endphp

<div>
    <div class="rise mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-500">{{ now()->format('l, F j, Y') }}</p>
            <h1 class="page-title mt-1">{{ $greeting }}, {{ explode(' ', auth()->user()->name ?? 'there')[0] }}</h1>
            <p class="muted mt-1">A calm look at your 15-day cycle, savings, and accounts.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="Livewire.dispatch('open-quick-add')" class="btn-primary">
                <x-icon name="plus" class="h-4 w-4" /> Add expense
            </button>
            <a href="{{ route('paydays') }}" wire:navigate class="btn-ghost">
                <x-icon name="paydays" class="h-4 w-4" /> Log payday
            </a>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        <div class="space-y-5 xl:col-span-2">
            {{-- Payday hero --}}
            @if ($advice)
                <section class="card hero-glow rise relative overflow-hidden border-white/10 bg-gradient-to-br from-slate-900 via-slate-900 to-emerald-950 p-5 text-white shadow-2xl shadow-emerald-950/30 sm:p-7">
                    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(52,211,153,0.25),transparent_45%)]"></div>
                    <div class="relative flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-emerald-300/80">Current pay period</p>
                            <p class="mt-1 text-sm font-semibold text-slate-200">
                                {{ $currentPeriod->label() }} · {{ $currentPeriod->days() }} days
                            </p>
                        </div>
                        <span class="chip {{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>

                    <div class="relative mt-5 grid gap-6 lg:grid-cols-[1.1fr_.9fr] lg:items-end">
                        <div>
                            <p class="text-xs font-medium text-slate-400">Move to savings on this payday</p>
                            <p class="mt-1 text-4xl font-extrabold tracking-tight tnum sm:text-5xl">
                                <span data-countup="{{ $advice['target'] }}" data-prefix="{{ $symbol }}" data-decimals="0">{{ \App\Support\Money::format($advice['target'], true, 0) }}</span>
                            </p>
                            <p class="mt-3 max-w-xl text-sm leading-6 text-slate-300">{{ $advice['advice'] }}</p>

                            <div class="mt-5">
                                <div class="mb-2 flex items-center justify-between text-[11px] font-medium text-slate-400">
                                    <span>Saved this period</span>
                                    <span class="tnum text-slate-200">{{ \App\Support\Money::format($advice['actual_saved']) }} / {{ \App\Support\Money::format($advice['target']) }}</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-white/10">
                                    <div class="h-full rounded-full bg-gradient-to-r from-emerald-300 to-cyan-300" style="width: {{ $savedPercent }}%"></div>
                                </div>
                            </div>

                            <div class="mt-5 flex flex-wrap gap-2">
                                <a href="{{ route('savings') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-slate-900 transition hover:bg-emerald-100">
                                    <x-icon name="savings" class="h-4 w-4" /> Save now
                                </a>
                                <a href="{{ route('paydays') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10">
                                    View paydays
                                </a>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-2xl border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Safe to spend</p>
                                <p class="mt-1 text-xl font-bold tnum">{{ \App\Support\Money::format($advice['safe_to_spend']) }}</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Daily guide</p>
                                <p class="mt-1 text-xl font-bold tnum">{{ \App\Support\Money::format($advice['daily']) }}</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Bills due</p>
                                <p class="mt-1 text-xl font-bold tnum">{{ \App\Support\Money::format($advice['bills_due']) }}</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Net salary</p>
                                <p class="mt-1 text-xl font-bold tnum">{{ \App\Support\Money::format($advice['net']) }}</p>
                            </div>
                        </div>
                    </div>

                    @if ($advice['shortfall'])
                        <div class="relative mt-4 flex items-start gap-2 rounded-xl border border-amber-400/20 bg-amber-400/10 p-3 text-xs text-amber-200">
                            <x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />
                            Bills this period exceed the salary minus your target. Save up to {{ \App\Support\Money::format($advice['max_save']) }} or trim a bill.
                        </div>
                    @endif
                </section>
            @else
                <section class="card hero-glow rise border-emerald-500/20 p-6 sm:p-8">
                    <div class="flex flex-wrap items-center gap-4">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-400/15 text-emerald-500">
                            <x-icon name="paydays" class="h-6 w-6" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Start your first 15-day cycle</h2>
                            <p class="muted mt-1">Log the salary you received and Budget Desk will calculate your savings target, bills, and daily safe-to-spend amount.</p>
                        </div>
                        <a href="{{ route('paydays') }}" wire:navigate class="btn-primary">Log first payday</a>
                    </div>
                </section>
            @endif

            {{-- Stat strip --}}
            <div class="stagger grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="card card-hover p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total cash</p>
                        <x-icon name="wallet" class="h-4 w-4 text-slate-300 dark:text-slate-600" />
                    </div>
                    <p class="mt-2 text-xl font-extrabold tnum text-slate-900 dark:text-white" data-countup="{{ $totalCash }}" data-prefix="{{ $symbol }}" data-decimals="0">{{ \App\Support\Money::format($totalCash, true, 0) }}</p>
                    <p class="mt-1 text-[11px] text-slate-400">across {{ $accounts->count() }} accounts</p>
                </div>
                <div class="card card-hover p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Spent this month</p>
                        <x-icon name="expenses" class="h-4 w-4 text-rose-300" />
                    </div>
                    <p class="mt-2 text-xl font-extrabold tnum text-slate-900 dark:text-white" data-countup="{{ $summary['expenses'] }}" data-prefix="{{ $symbol }}" data-decimals="0">{{ \App\Support\Money::format($summary['expenses'], true, 0) }}</p>
                    <p class="mt-1 text-[11px] text-slate-400">of {{ \App\Support\Money::format($budgetTotal, true, 0) }} budget</p>
                </div>
                <div class="card card-hover p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Saved this month</p>
                        <x-icon name="savings" class="h-4 w-4 text-emerald-400" />
                    </div>
                    <p class="mt-2 text-xl font-extrabold tnum text-slate-900 dark:text-white" data-countup="{{ $summary['saved'] }}" data-prefix="{{ $symbol }}" data-decimals="0">{{ \App\Support\Money::format($summary['saved'], true, 0) }}</p>
                    <p class="mt-1 text-[11px] text-slate-400">{{ $activeGoals->count() }} active goals</p>
                </div>
                <div class="card card-hover p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Next payday</p>
                        <x-icon name="calendar" class="h-4 w-4 text-cyan-300" />
                    </div>
                    <p class="mt-2 text-xl font-extrabold tnum text-slate-900 dark:text-white">
                        {{ $daysToPayday === 0 ? 'Today' : $daysToPayday.'d' }}
                    </p>
                    <p class="mt-1 text-[11px] text-slate-400">{{ $nextPayday->format('M j, Y') }}</p>
                </div>
            </div>

            {{-- Cashflow chart --}}
            <section class="card rise p-5 sm:p-6">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Cashflow</h2>
                        <p class="text-xs text-slate-400">Income, spending, and savings over six months</p>
                    </div>
                    <a href="{{ route('reports') }}" wire:navigate class="text-xs font-semibold text-emerald-600 hover:text-emerald-500 dark:text-emerald-400">Full report →</a>
                </div>
                <div class="h-[250px]"><canvas id="cashflowChart"></canvas></div>
            </section>

            {{-- Budget + top categories --}}
            <section class="card rise p-5 sm:p-6">
                <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">This month's budget</h2>
                        <p class="text-xs text-slate-400">{{ now()->format('F Y') }}</p>
                    </div>
                    <a href="{{ route('budgets') }}" wire:navigate class="btn-ghost px-3 py-1.5 text-xs">Edit budgets</a>
                </div>

                @if ($budgetTotal > 0)
                    <div class="flex items-end justify-between gap-3">
                        <div>
                            <p class="text-2xl font-extrabold tnum text-slate-900 dark:text-white">{{ \App\Support\Money::format($spentTotal) }}</p>
                            <p class="text-xs text-slate-400">of {{ \App\Support\Money::format($budgetTotal) }} spent</p>
                        </div>
                        <p class="text-sm font-bold {{ $spentTotal > $budgetTotal ? 'text-rose-500' : 'text-emerald-500' }}">{{ $budgetPercent }}%</p>
                    </div>
                    <div class="progress-track mt-3">
                        <div class="progress-fill {{ $spentTotal > $budgetTotal ? 'warm' : '' }}" style="--w: {{ $budgetPercent }}%"></div>
                    </div>
                @else
                    <p class="text-sm text-slate-400">No budgets set yet. Add monthly limits to see how fast you are spending.</p>
                @endif

                <div class="mt-5 space-y-3">
                    @forelse ($topCategories as $row)
                        <div class="flex items-center gap-3">
                            <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $row['color'] }}"></span>
                            <span class="min-w-0 flex-1 truncate text-sm text-slate-600 dark:text-slate-300">{{ $row['name'] }}</span>
                            <span class="text-sm font-semibold tnum text-slate-800 dark:text-slate-100">{{ \App\Support\Money::format($row['total']) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">No spending logged this month yet.</p>
                    @endforelse
                </div>
            </section>

            {{-- Recent transactions --}}
            <section class="card rise p-5 sm:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Recent activity</h2>
                        <p class="text-xs text-slate-400">Latest money movements</p>
                    </div>
                    <a href="{{ route('expenses') }}" wire:navigate class="text-xs font-semibold text-emerald-600 hover:text-emerald-500 dark:text-emerald-400">All expenses →</a>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-white/[0.06]">
                    @forelse ($recent as $row)
                        <div class="flex items-center gap-3 py-3">
                            <span @class([
                                'grid h-9 w-9 place-items-center rounded-xl',
                                'bg-emerald-400/10 text-emerald-500' => $row['kind'] === 'income',
                                'bg-cyan-400/10 text-cyan-500' => $row['kind'] === 'transfer',
                                'bg-slate-400/10 text-slate-500 dark:text-slate-300' => $row['kind'] === 'expense',
                            ])>
                                <x-icon :name="$row['kind'] === 'income' ? 'arrow-down' : ($row['kind'] === 'transfer' ? 'arrow-right' : 'expenses')" class="h-4 w-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $row['label'] }}</p>
                                <p class="truncate text-[11px] text-slate-400">{{ $row['meta'] }} · {{ $row['date']->format('M j') }}</p>
                            </div>
                            @if ($row['kind'] === 'transfer')
                                <p class="text-sm font-bold tnum text-cyan-500">{{ \App\Support\Money::format($row['movement'] ?? 0) }}</p>
                            @else
                                <p @class([
                                    'text-sm font-bold tnum',
                                    'text-emerald-500' => $row['amount'] > 0,
                                    'text-slate-500 dark:text-slate-400' => $row['amount'] == 0,
                                    'text-slate-800 dark:text-slate-200' => $row['amount'] < 0,
                                ])>
                                    {{ $row['amount'] > 0 ? '+' : ($row['amount'] < 0 ? '−' : '') }}{{ \App\Support\Money::format(abs($row['amount'])) }}
                                </p>
                            @endif
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-slate-400">Nothing here yet. Log a payday or an expense to begin.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="space-y-5">
            {{-- Accounts --}}
            <section class="card rise p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Accounts</h2>
                    <a href="{{ route('accounts') }}" wire:navigate class="text-xs font-semibold text-emerald-600 hover:text-emerald-500 dark:text-emerald-400">Manage</a>
                </div>
                <div class="space-y-3">
                    @forelse ($accounts as $account)
                        <div class="flex items-center gap-3 rounded-xl border border-transparent px-1 py-1.5 transition hover:border-slate-100 hover:bg-slate-50/60 dark:hover:border-white/5 dark:hover:bg-white/[0.03]">
                            <span class="grid h-9 w-9 place-items-center rounded-xl text-white" style="background: {{ $account->color ?: '#0f172a' }}">
                                <x-icon :name="$account->counts_as_savings ? 'savings' : 'wallet'" class="h-4 w-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $account->name }}</p>
                                <p class="text-[10px] uppercase tracking-wider text-slate-400">{{ $account->typeLabel() }}{{ $account->counts_as_savings ? ' · savings' : '' }}</p>
                            </div>
                            <p class="text-sm font-bold tnum text-slate-800 dark:text-slate-100">{{ \App\Support\Money::format($balanceMap[$account->id] ?? 0) }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">No accounts yet.</p>
                        <a href="{{ route('accounts') }}" wire:navigate class="btn-ghost mt-2 w-full">Add an account</a>
                    @endforelse
                </div>
                @if ($accounts->count())
                    <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-xs dark:border-white/[0.06]">
                        <span class="text-slate-400">Savings balance</span>
                        <span class="font-bold text-emerald-500 tnum">{{ \App\Support\Money::format($totalSavings) }}</span>
                    </div>
                @endif
            </section>

            {{-- Category chart --}}
            @if (count($charts[1]['data']['labels']))
                <section class="card rise p-5">
                    <div class="mb-2 flex items-center justify-between">
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Where it went</h2>
                        <span class="text-[10px] uppercase tracking-wider text-slate-400">{{ now()->format('M') }}</span>
                    </div>
                    <div class="relative h-[190px]">
                        <canvas id="categoryChart"></canvas>
                        <div class="pointer-events-none absolute inset-0 grid place-items-center">
                            <div class="text-center">
                                <p class="text-[9px] uppercase tracking-widest text-slate-400">Spent</p>
                                <p class="text-base font-extrabold tnum text-slate-800 dark:text-slate-100">{{ \App\Support\Money::format($summary['expenses'], true, 0) }}</p>
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            {{-- Upcoming bills --}}
            <section class="card rise p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Upcoming bills</h2>
                    <a href="{{ route('bills') }}" wire:navigate class="text-xs font-semibold text-emerald-600 hover:text-emerald-500 dark:text-emerald-400">All</a>
                </div>
                <div class="space-y-3">
                    @forelse ($upcomingBills as $row)
                        <div class="flex items-center gap-3">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-amber-400/10 text-amber-500">
                                <x-icon name="bills" class="h-4 w-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $row['bill']->name }}</p>
                                <p class="text-[11px] text-slate-400">
                                    Due {{ $row['due']->format('M j') }}
                                    @if ($row['paid']) · <span class="text-emerald-500">paid</span> @endif
                                </p>
                            </div>
                            <p class="text-sm font-bold tnum text-slate-800 dark:text-slate-100">{{ \App\Support\Money::format($row['bill']->amount, true, 0) }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">No bills tracked.</p>
                        <a href="{{ route('bills') }}" wire:navigate class="btn-ghost mt-2 w-full">Add a bill</a>
                    @endforelse
                </div>
            </section>

            {{-- Emergency fund --}}
            <section class="card rise p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Emergency fund</h2>
                    <a href="{{ route('savings') }}" wire:navigate class="text-xs font-semibold text-emerald-600 hover:text-emerald-500 dark:text-emerald-400">Open</a>
                </div>
                @if ($emergencyGoal)
                    <p class="mt-3 text-2xl font-extrabold tnum text-slate-900 dark:text-white">{{ \App\Support\Money::format($emergencyGoal->progressAmount(), true, 0) }}</p>
                    <p class="text-xs text-slate-400">of {{ \App\Support\Money::format($emergencyGoal->effectiveTarget(), true, 0) }} · {{ $emergencyGoal->progressPercent() }}%</p>
                    <div class="progress-track mt-3">
                        <div class="progress-fill" style="--w: {{ $emergencyGoal->progressPercent() }}%"></div>
                    </div>
                    <p class="mt-2 text-[10px] text-slate-400">Target based on {{ $emergencyGoal->targetSourceLabel() }}.</p>
                @else
                    <p class="mt-3 text-sm text-slate-400">Create an emergency fund goal to track your safety net.</p>
                    <a href="{{ route('savings') }}" wire:navigate class="btn-ghost mt-3 w-full">Create goal</a>
                @endif
            </section>
        </div>
    </div>

    <div id="chart-payload" class="hidden" data-charts="{{ json_encode($charts) }}"></div>
</div>
