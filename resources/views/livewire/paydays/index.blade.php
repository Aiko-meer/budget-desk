@php $symbol = \App\Models\Settings::current()->currency_symbol; @endphp

<div>
    <div class="rise mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-500">15-day cycle</p>
            <h1 class="page-title mt-1">Paydays</h1>
            <p class="muted mt-1">Log each salary and see the savings target it creates.</p>
        </div>
        <button type="button" wire:click="openCreate" class="btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> Log payday
        </button>
    </div>

    <div class="stagger mb-5 grid gap-3 sm:grid-cols-3">
        <div class="card card-hover p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Income this year</p>
            <p class="mt-2 text-xl font-extrabold tnum text-slate-900 dark:text-white">{{ \App\Support\Money::format($yearIncome, true, 0) }}</p>
            <p class="mt-1 text-[11px] text-slate-400">{{ now()->year }} paydays</p>
        </div>
        <div class="card card-hover p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Average per payday</p>
            <p class="mt-2 text-xl font-extrabold tnum text-slate-900 dark:text-white">{{ \App\Support\Money::format($average, true, 0) }}</p>
            <p class="mt-1 text-[11px] text-slate-400">based on logged salaries</p>
        </div>
        <div class="card card-hover p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Next scheduled payday</p>
            <p class="mt-2 text-xl font-extrabold tnum text-slate-900 dark:text-white">{{ $nextPayday->format('M j') }}</p>
            <p class="mt-1 text-[11px] text-slate-400">15th &amp; last day of month</p>
        </div>
    </div>

    <div class="card rise mb-5 border-emerald-500/15 bg-emerald-500/[0.04] p-4">
        <p class="flex items-start gap-2 text-xs leading-5 text-slate-600 dark:text-slate-300">
            <x-icon name="clock" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" />
            A payday starts a new spending period. Salary received on the 15th covers the 15th through the day before the next payday; salary received at month-end covers the end of the month through the 14th.
        </p>
    </div>

    <section class="card rise overflow-hidden">
        <div class="border-b border-slate-100 p-5 dark:border-white/[0.06]">
            <h2 class="text-base font-bold text-slate-900 dark:text-white">Salary history</h2>
            <p class="text-xs text-slate-400">Latest {{ $rows->count() }} paydays</p>
        </div>

        @if ($rows->count())
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-100 dark:border-white/[0.06]">
                        <tr>
                            <th class="table-head px-5 py-3">Payday</th>
                            <th class="table-head px-5 py-3">Period</th>
                            <th class="table-head px-5 py-3 text-right">Net salary</th>
                            <th class="table-head px-5 py-3 text-right">Target</th>
                            <th class="table-head px-5 py-3 text-right">Saved</th>
                            <th class="table-head px-5 py-3">Status</th>
                            <th class="table-head px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/[0.06]">
                        @foreach ($rows as $row)
                            <tr class="group transition hover:bg-slate-50/60 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-3.5">
                                    <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $row['payday']->pay_date->format('M j, Y') }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $row['payday']->account?->name }}</p>
                                </td>
                                <td class="px-5 py-3.5 text-xs text-slate-500 dark:text-slate-400">{{ $row['period']->label() }}</td>
                                <td class="px-5 py-3.5 text-right font-semibold tnum text-slate-800 dark:text-slate-100">{{ \App\Support\Money::format($row['payday']->net_amount) }}</td>
                                <td class="px-5 py-3.5 text-right font-semibold tnum text-emerald-600 dark:text-emerald-400">{{ \App\Support\Money::format($row['advice']['target']) }}</td>
                                <td class="px-5 py-3.5 text-right tnum text-slate-600 dark:text-slate-300">{{ \App\Support\Money::format($row['advice']['actual_saved']) }}</td>
                                <td class="px-5 py-3.5">
                                    @php
                                        $statusClass = match ($row['advice']['status']) {
                                            'met' => 'bg-emerald-400/10 text-emerald-600 dark:text-emerald-300',
                                            'short' => 'bg-rose-400/10 text-rose-500 dark:text-rose-300',
                                            default => 'bg-cyan-400/10 text-cyan-600 dark:text-cyan-300',
                                        };
                                        $statusText = match ($row['advice']['status']) {
                                            'met' => 'On target',
                                            'short' => 'Short '.\App\Support\Money::format(abs($row['advice']['difference'])),
                                            default => 'Ahead '.\App\Support\Money::format(abs($row['advice']['difference'])),
                                        };
                                    @endphp
                                    <span class="chip {{ $statusClass }}">{{ $statusText }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex justify-end gap-1 opacity-100 transition md:opacity-0 md:group-hover:opacity-100">
                                        <button class="btn-icon h-8 w-8" wire:click="openEdit({{ $row['payday']->id }})" aria-label="Edit">
                                            <x-icon name="pencil" class="h-3.5 w-3.5" />
                                        </button>
                                        <button class="btn-icon h-8 w-8 text-rose-500" wire:click="delete({{ $row['payday']->id }})" wire:confirm="Delete this payday? Expenses and transfers stay." aria-label="Delete">
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
                @foreach ($rows as $row)
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $row['payday']->pay_date->format('M j, Y') }}</p>
                                <p class="text-[11px] text-slate-400">{{ $row['period']->label() }} · {{ $row['payday']->account?->name }}</p>
                            </div>
                            <span class="chip {{ $row['advice']['status'] === 'met' ? 'bg-emerald-400/10 text-emerald-600 dark:text-emerald-300' : ($row['advice']['status'] === 'short' ? 'bg-rose-400/10 text-rose-500' : 'bg-cyan-400/10 text-cyan-500') }}">
                                {{ $row['advice']['status'] === 'met' ? 'On target' : ucfirst($row['advice']['status']) }}
                            </span>
                        </div>
                        <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                            <div class="rounded-lg bg-slate-50 p-2 dark:bg-white/5">
                                <p class="text-[9px] uppercase tracking-wider text-slate-400">Net</p>
                                <p class="text-xs font-bold tnum">{{ \App\Support\Money::format($row['payday']->net_amount, true, 0) }}</p>
                            </div>
                            <div class="rounded-lg bg-emerald-500/5 p-2">
                                <p class="text-[9px] uppercase tracking-wider text-slate-400">Target</p>
                                <p class="text-xs font-bold tnum text-emerald-500">{{ \App\Support\Money::format($row['advice']['target'], true, 0) }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-2 dark:bg-white/5">
                                <p class="text-[9px] uppercase tracking-wider text-slate-400">Saved</p>
                                <p class="text-xs font-bold tnum">{{ \App\Support\Money::format($row['advice']['actual_saved'], true, 0) }}</p>
                            </div>
                        </div>
                        <div class="mt-3 flex gap-1">
                            <button class="btn-ghost px-2 py-1 text-[11px]" wire:click="openEdit({{ $row['payday']->id }})">Edit</button>
                            <button class="btn-danger px-2 py-1 text-[11px]" wire:click="delete({{ $row['payday']->id }})" wire:confirm="Delete this payday?">Delete</button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="grid place-items-center px-6 py-16 text-center">
                <span class="grid h-14 w-14 place-items-center rounded-2xl bg-emerald-400/10 text-emerald-500">
                    <x-icon name="paydays" class="h-7 w-7" />
                </span>
                <h3 class="mt-4 text-base font-bold text-slate-900 dark:text-white">No paydays logged</h3>
                <p class="mt-1 max-w-sm text-sm text-slate-400">Log your first salary to unlock the savings advice and pay-period tracking.</p>
                <button wire:click="openCreate" class="btn-primary mt-4"><x-icon name="plus" class="h-4 w-4" /> Log payday</button>
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
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ $editingId ? 'Update payday' : 'Log payday' }}</h2>
                    </div>
                    <button class="btn-icon h-8 w-8" wire:click="$set('showForm', false)" aria-label="Close"><x-icon name="x" class="h-4 w-4" /></button>
                </div>

                <form wire:submit="save" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Payday date</label>
                            <input type="date" wire:model="pay_date" class="input">
                            @error('pay_date') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Deposit account</label>
                            <select wire:model="account_id" class="input">
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                            @error('account_id') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Net salary received</label>
                            <input type="number" step="0.01" min="0" wire:model="net_amount" class="input tnum text-lg font-bold">
                            @error('net_amount') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Gross salary <span class="normal-case tracking-normal text-slate-400">(optional)</span></label>
                            <input type="number" step="0.01" min="0" wire:model="gross_amount" class="input tnum">
                            @error('gross_amount') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="label">Notes <span class="normal-case tracking-normal text-slate-400">(optional)</span></label>
                        <input type="text" wire:model="notes" class="input" placeholder="Bonus, overtime, adjustment...">
                        @error('notes') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button type="button" wire:click="$set('showForm', false)" class="btn-ghost flex-1">Cancel</button>
                        <button type="submit" class="btn-primary flex-1" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">{{ $editingId ? 'Save changes' : 'Log payday' }}</span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
