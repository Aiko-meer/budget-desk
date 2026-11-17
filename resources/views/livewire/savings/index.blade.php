@php
    $statusMap = [
        'met' => ['On target', 'bg-emerald-400/10 text-emerald-600 dark:text-emerald-300'],
        'short' => ['Short', 'bg-rose-400/10 text-rose-600 dark:text-rose-300'],
        'ahead' => ['Ahead', 'bg-cyan-400/10 text-cyan-600 dark:text-cyan-300'],
    ];
    $savedPercent = ($advice['target'] ?? 0) > 0
        ? min(100, round((($advice['actual_saved'] ?? 0) / $advice['target']) * 100, 1))
        : 0;
    $emergencyPercent = $emergency?->progressPercent() ?? 0;
@endphp

<div>
    <div class="rise mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-500">Future you</p>
            <h1 class="page-title mt-1">Savings</h1>
            <p class="muted mt-1">Every payday has a target. Every goal has a finish line.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="openGoalCreate" class="btn-ghost">
                <x-icon name="target" class="h-4 w-4" /> New goal
            </button>
            <button type="button" wire:click="openSave" class="btn-primary">
                <x-icon name="savings" class="h-4 w-4" /> Save now
            </button>
        </div>
    </div>

    {{-- Hero: this payday's target --}}
    @if ($advice)
        <section class="card hero-glow rise relative mb-5 overflow-hidden border-white/10 bg-gradient-to-br from-slate-900 via-slate-900 to-emerald-950 p-5 text-white shadow-2xl shadow-emerald-950/30 sm:p-7">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(52,211,153,0.25),transparent_45%)]"></div>
            <div class="relative grid gap-6 lg:grid-cols-[1.1fr_.9fr] lg:items-center">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-emerald-300/80">Payday {{ $advice['period']->start->format('M j') }} target</p>
                    <p class="mt-2 text-4xl font-extrabold tracking-tight tnum sm:text-5xl">
                        <span data-countup="{{ $advice['target'] }}" data-prefix="{{ $symbol }}" data-decimals="0">{{ \App\Support\Money::format($advice['target'], true, 0) }}</span>
                    </p>
                    <p class="mt-2 text-sm leading-6 text-slate-300">{{ $advice['advice'] }}</p>

                    <div class="mt-5">
                        <div class="mb-2 flex items-center justify-between text-[11px] font-medium text-slate-400">
                            <span>Saved so far this period</span>
                            <span class="tnum text-slate-200">{{ \App\Support\Money::format($advice['actual_saved']) }} / {{ \App\Support\Money::format($advice['target']) }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-white/10">
                            <div class="progress-fill" style="--w: {{ $savedPercent }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-2xl border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Total savings</p>
                        <p class="mt-1 text-xl font-bold tnum">{{ \App\Support\Money::format($totalSavings) }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Saved this month</p>
                        <p class="mt-1 text-xl font-bold tnum">{{ \App\Support\Money::format($monthSaved) }}</p>
                    </div>
                    <div class="col-span-2 flex flex-wrap gap-2 pt-1">
                        <button wire:click="openSave" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-slate-900 transition hover:bg-emerald-100">
                            <x-icon name="plus" class="h-4 w-4" /> Save {{ \App\Support\Money::format($advice['target'], true, 0) }} now
                        </button>
                        <a href="{{ route('accounts') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10">
                            Accounts
                        </a>
                    </div>
                </div>
            </div>
        </section>
    @else
        <section class="card rise mb-5 border-emerald-500/20 p-6">
            <p class="text-sm text-slate-500 dark:text-slate-300">Log a payday to see the amount this cycle should send to savings.</p>
            <a href="{{ route('paydays') }}" wire:navigate class="btn-primary mt-3">Log payday</a>
        </section>
    @endif

    {{-- Stats --}}
    <div class="stagger mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="card p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Savings balance</p>
            <p class="mt-2 text-xl font-extrabold tnum text-slate-900 dark:text-white" data-countup="{{ $totalSavings }}" data-prefix="{{ $symbol }}" data-decimals="0">{{ \App\Support\Money::format($totalSavings, true, 0) }}</p>
            <p class="mt-1 text-[11px] text-slate-400">across {{ $savingsAccounts->count() }} savings accounts</p>
        </div>
        <div class="card p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Saved this month</p>
            <p class="mt-2 text-xl font-extrabold tnum text-emerald-500">{{ \App\Support\Money::format($monthSaved, true, 0) }}</p>
            <p class="mt-1 text-[11px] text-slate-400">{{ now()->format('F Y') }}</p>
        </div>
        <div class="card p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Active goals</p>
            <p class="mt-2 text-xl font-extrabold tnum text-slate-900 dark:text-white">{{ $activeGoals->count() }}</p>
            <p class="mt-1 text-[11px] text-slate-400">{{ $goals->count() }} total</p>
        </div>
        <div class="card p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Emergency fund</p>
            @if ($emergency)
                <p class="mt-2 text-xl font-extrabold tnum text-slate-900 dark:text-white">{{ $emergencyPercent }}%</p>
                <div class="progress-track mt-2">
                    <div class="progress-fill" style="--w: {{ $emergencyPercent }}%"></div>
                </div>
            @else
                <p class="mt-2 text-xl font-extrabold text-slate-400">—</p>
                <p class="mt-1 text-[11px] text-slate-400">No goal yet</p>
            @endif
        </div>
    </div>

    {{-- Goals --}}
    <section class="rise mb-5">
        <div class="mb-3 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Goals</h2>
                <p class="text-xs text-slate-400">Tag transfers to a goal and watch the bar fill up.</p>
            </div>
            <button wire:click="openGoalCreate" class="btn-ghost px-3 py-1.5 text-xs">Add goal</button>
        </div>

        <div class="stagger grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($goals as $goal)
                @php
                    $progress = $goal->progressAmount();
                    $target = $goal->effectiveTarget();
                    $percent = $goal->progressPercent();
                    $complete = $target > 0 && $progress >= $target;
                @endphp
                <article class="card card-hover relative overflow-hidden p-5 {{ $goal->is_active ? '' : 'opacity-60' }}">
                    <span class="absolute inset-x-0 top-0 h-1" style="background: {{ $goal->color ?: '#34d399' }}"></span>
                    <div class="flex items-start justify-between gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-xl text-white shadow-lg" style="background: {{ $goal->color ?: '#34d399' }}">
                            <x-icon :name="$goal->is_emergency_fund ? 'savings' : 'target'" class="h-5 w-5" />
                        </span>
                        <div class="flex gap-1">
                            <button class="btn-icon h-8 w-8" wire:click="openGoalEdit({{ $goal->id }})" title="Edit" aria-label="Edit goal">
                                <x-icon name="pencil" class="h-3.5 w-3.5" />
                            </button>
                            <button class="btn-icon h-8 w-8 {{ $goal->is_active ? 'text-rose-500' : 'text-emerald-500' }}" wire:click="toggleGoal({{ $goal->id }})" title="{{ $goal->is_active ? 'Archive' : 'Reactivate' }}" aria-label="Toggle goal">
                                <x-icon :name="$goal->is_active ? 'x' : 'check'" class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-1.5">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ $goal->name }}</h3>
                        @if ($goal->is_emergency_fund)
                            <span class="chip bg-amber-400/10 text-amber-600 dark:text-amber-300">Emergency</span>
                        @endif
                        @if ($complete)
                            <span class="chip bg-emerald-400/15 text-emerald-600 dark:text-emerald-300">Reached</span>
                        @elseif (! $goal->is_active)
                            <span class="chip bg-slate-400/10 text-slate-500">Archived</span>
                        @endif
                    </div>

                    <p class="mt-3 text-2xl font-extrabold tnum text-slate-900 dark:text-white">{{ \App\Support\Money::format($progress) }}</p>
                    <p class="text-[11px] text-slate-400">
                        of {{ \App\Support\Money::format($target) }}
                        @if ($goal->target_date) · by {{ $goal->target_date->format('M j, Y') }} @endif
                    </p>

                    <div class="mt-3 flex items-center gap-2">
                        <div class="progress-track flex-1">
                            <div class="progress-fill" style="--w: {{ $percent }}%"></div>
                        </div>
                        <span class="text-xs font-bold tnum {{ $complete ? 'text-emerald-500' : 'text-slate-500 dark:text-slate-300' }}">{{ $percent }}%</span>
                    </div>

                    <p class="mt-2 text-[10px] text-slate-400">Target based on {{ $goal->targetSourceLabel() }}{{ $goal->target_date ? '' : ' · no deadline' }}.</p>

                    @if ($goal->notes)
                        <p class="mt-2 line-clamp-2 text-[11px] text-slate-400">{{ $goal->notes }}</p>
                    @endif
                </article>
            @empty
                <div class="card md:col-span-2 xl:col-span-3 p-8 text-center">
                    <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-emerald-400/10 text-emerald-500">
                        <x-icon name="target" class="h-6 w-6" />
                    </span>
                    <h3 class="mt-3 text-sm font-bold text-slate-900 dark:text-white">No goals yet</h3>
                    <p class="mt-1 text-xs text-slate-400">Start with an emergency fund — three months of expenses is the usual target.</p>
                    <button wire:click="openGoalCreate" class="btn-primary mt-4">Create first goal</button>
                </div>
            @endforelse
        </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-2">
        {{-- Per-payday record --}}
        <section class="card rise overflow-hidden">
            <div class="border-b border-slate-100 p-5 dark:border-white/[0.06]">
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Payday record</h2>
                <p class="text-xs text-slate-400">Target vs. actually saved, last six periods</p>
            </div>
            @if ($paydayRows->count())
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-slate-100 dark:border-white/[0.06]">
                            <tr>
                                <th class="table-head px-5 py-3">Period</th>
                                <th class="table-head px-5 py-3 text-right">Target</th>
                                <th class="table-head px-5 py-3 text-right">Saved</th>
                                <th class="table-head px-5 py-3 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            @foreach ($paydayRows as $row)
                                @php [$label, $class] = $statusMap[$row['status']] ?? $statusMap['met']; @endphp
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-white/[0.02]">
                                    <td class="whitespace-nowrap px-5 py-3">
                                        <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $row['period']->label() }}</p>
                                        <p class="text-[10px] text-slate-400">{{ $row['period']->payday->pay_date->format('M j, Y') }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-right text-xs font-semibold tnum text-slate-700 dark:text-slate-200">{{ \App\Support\Money::format($row['target']) }}</td>
                                    <td class="px-5 py-3 text-right text-xs font-semibold tnum {{ $row['actual_saved'] >= $row['target'] ? 'text-emerald-500' : 'text-slate-700 dark:text-slate-200' }}">{{ \App\Support\Money::format($row['actual_saved']) }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <span class="chip {{ $class }}">{{ $label }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="p-6 text-center text-sm text-slate-400">No paydays logged yet.</p>
            @endif
        </section>

        {{-- Contributions --}}
        <section class="card rise overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 p-5 dark:border-white/[0.06]">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Recent contributions</h2>
                    <p class="text-xs text-slate-400">Money moved into savings accounts</p>
                </div>
                <button wire:click="openSave" class="btn-ghost px-3 py-1.5 text-xs">Save now</button>
            </div>
            @if ($contributions->count())
                <div class="max-h-[420px] divide-y divide-slate-100 overflow-y-auto dark:divide-white/[0.06]">
                    @foreach ($contributions as $transfer)
                        <div class="group flex items-center gap-3 px-5 py-3">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-400/10 text-emerald-500">
                                <x-icon name="arrow-right" class="h-4 w-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">
                                    {{ $transfer->fromAccount?->name }} → {{ $transfer->toAccount?->name }}
                                </p>
                                <p class="truncate text-[11px] text-slate-400">
                                    {{ $transfer->transferred_on->format('M j, Y') }}
                                    @if ($transfer->goal) · {{ $transfer->goal->name }} @endif
                                    @if ($transfer->note) · {{ $transfer->note }} @endif
                                </p>
                            </div>
                            <p class="text-sm font-bold tnum text-emerald-500">+{{ \App\Support\Money::format($transfer->amount) }}</p>
                            <button class="btn-icon h-8 w-8 text-rose-500 opacity-100 md:opacity-0 md:group-hover:opacity-100" wire:click="deleteTransfer({{ $transfer->id }})" wire:confirm="Delete this savings entry?" aria-label="Delete contribution">
                                <x-icon name="trash" class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center">
                    <p class="text-sm text-slate-400">No contributions yet. The first transfer is the hardest — then it becomes a habit.</p>
                    <button wire:click="openSave" class="btn-primary mt-4">Make first transfer</button>
                </div>
            @endif
        </section>
    </div>

    {{-- Goal form --}}
    @if ($showGoalForm)
        <div class="fixed inset-0 z-[90] flex items-end justify-center p-3 sm:items-center sm:p-6">
            <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" wire:click="$set('showGoalForm', false)"></div>
            <div class="card modal-pop relative max-h-[90vh] w-full max-w-lg overflow-y-auto p-5 sm:p-6">
                <div class="mb-5 flex items-start justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-500">{{ $goalId ? 'Edit' : 'New' }}</p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ $goalId ? 'Update goal' : 'Create goal' }}</h2>
                    </div>
                    <button class="btn-icon h-8 w-8" wire:click="$set('showGoalForm', false)" aria-label="Close"><x-icon name="x" class="h-4 w-4" /></button>
                </div>

                <form wire:submit="saveGoal" class="space-y-4">
                    <div>
                        <label class="label">Goal name</label>
                        <input type="text" wire:model="name" class="input" placeholder="Emergency fund, Japan trip...">
                        @error('name') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Target amount <span class="normal-case tracking-normal text-slate-400">(blank = auto)</span></label>
                            <input type="number" step="0.01" min="0" wire:model="target_amount" class="input tnum" placeholder="Auto from expenses">
                            @error('target_amount') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Target date <span class="normal-case tracking-normal text-slate-400">(optional)</span></label>
                            <input type="date" wire:model="target_date" class="input">
                            @error('target_date') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <label class="flex items-start gap-2 rounded-xl border border-amber-400/20 bg-amber-400/[0.06] p-3 text-xs text-slate-600 dark:text-slate-300">
                        <input type="checkbox" wire:model="is_emergency_fund" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-400 dark:border-white/20 dark:bg-white/5">
                        <span>This is my emergency fund. Leave the target blank to auto-calculate {{ (int) \App\Models\Settings::current()->emergency_fund_months }} months of expenses.</span>
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Accent color</label>
                            <input type="color" wire:model="color" class="h-10 w-full cursor-pointer rounded-xl border border-slate-200 bg-white p-1 dark:border-white/10 dark:bg-white/5">
                        </div>
                        <label class="flex items-center gap-2 pt-5 text-xs text-slate-500 dark:text-slate-400">
                            <input type="checkbox" wire:model="is_active" class="h-4 w-4 rounded border-slate-300 text-emerald-500 focus:ring-emerald-400 dark:border-white/20 dark:bg-white/5">
                            Active goal
                        </label>
                    </div>

                    <div>
                        <label class="label">Notes <span class="normal-case tracking-normal text-slate-400">(optional)</span></label>
                        <input type="text" wire:model="goal_notes" class="input" placeholder="Why this goal matters...">
                        @error('goal_notes') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex gap-2 pt-1">
                        <button type="button" wire:click="$set('showGoalForm', false)" class="btn-ghost flex-1">Cancel</button>
                        <button type="submit" class="btn-primary flex-1" wire:loading.attr="disabled" wire:target="saveGoal">
                            <span wire:loading.remove wire:target="saveGoal">{{ $goalId ? 'Save changes' : 'Create goal' }}</span>
                            <span wire:loading wire:target="saveGoal">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Save now form --}}
    @if ($showSaveForm)
        <div class="fixed inset-0 z-[90] flex items-end justify-center p-3 sm:items-center sm:p-6">
            <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" wire:click="$set('showSaveForm', false)"></div>
            <div class="card modal-pop relative w-full max-w-md p-5 sm:p-6">
                <div class="mb-5 flex items-start justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-500">Build the habit</p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">Save now</h2>
                    </div>
                    <button class="btn-icon h-8 w-8" wire:click="$set('showSaveForm', false)" aria-label="Close"><x-icon name="x" class="h-4 w-4" /></button>
                </div>

                <form wire:submit="saveTransfer" class="space-y-4">
                    <div>
                        <label class="label">Amount</label>
                        <input type="number" step="0.01" min="0.01" wire:model="amount" class="input tnum text-lg font-bold" placeholder="0.00">
                        @error('amount') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">From</label>
                            <select wire:model="from_account_id" class="input">
                                <option value="">Choose account</option>
                                @foreach ($sourceAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                            @error('from_account_id') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Into savings</label>
                            <select wire:model="to_account_id" class="input">
                                <option value="">Choose account</option>
                                @foreach ($savingsAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                            @error('to_account_id') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Date</label>
                            <input type="date" wire:model="transferred_on" max="{{ now()->toDateString() }}" class="input">
                            @error('transferred_on') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Goal <span class="normal-case tracking-normal text-slate-400">(optional)</span></label>
                            <select wire:model="goal_id" class="input">
                                <option value="">No goal</option>
                                @foreach ($activeGoals as $goal)
                                    <option value="{{ $goal->id }}">{{ $goal->name }}</option>
                                @endforeach
                            </select>
                            @error('goal_id') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="label">Note <span class="normal-case tracking-normal text-slate-400">(optional)</span></label>
                        <input type="text" wire:model="note" class="input" placeholder="15th payday savings...">
                    </div>

                    <div class="flex gap-2 pt-1">
                        <button type="button" wire:click="$set('showSaveForm', false)" class="btn-ghost flex-1">Cancel</button>
                        <button type="submit" class="btn-primary flex-1" wire:loading.attr="disabled" wire:target="saveTransfer">
                            <span wire:loading.remove wire:target="saveTransfer">Move to savings</span>
                            <span wire:loading wire:target="saveTransfer">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
