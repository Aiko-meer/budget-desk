@php $symbol = \App\Models\Settings::current()->currency_symbol; @endphp

<div>
    <div class="rise mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-500">Money map</p>
            <h1 class="page-title mt-1">Accounts</h1>
            <p class="muted mt-1">Balances are calculated from paydays, expenses, and transfers — never stored.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="openTransfer" class="btn-ghost">
                <x-icon name="arrow-right" class="h-4 w-4" /> Transfer
            </button>
            <button type="button" wire:click="openCreate" class="btn-primary">
                <x-icon name="plus" class="h-4 w-4" /> Add account
            </button>
        </div>
    </div>

    <div class="stagger mb-5 grid gap-3 sm:grid-cols-3">
        <div class="card p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total cash</p>
            <p class="mt-2 text-xl font-extrabold tnum text-slate-900 dark:text-white">{{ \App\Support\Money::format($totalCash, true, 0) }}</p>
        </div>
        <div class="card p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">In savings</p>
            <p class="mt-2 text-xl font-extrabold tnum text-emerald-500">{{ \App\Support\Money::format($totalSavings, true, 0) }}</p>
        </div>
        <div class="card p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Accounts</p>
            <p class="mt-2 text-xl font-extrabold tnum text-slate-900 dark:text-white">{{ $accounts->where('is_active', true)->count() }}</p>
            <p class="mt-1 text-[11px] text-slate-400">{{ $accounts->count() }} total, {{ $accounts->where('counts_as_savings', true)->count() }} savings</p>
        </div>
    </div>

    <div class="stagger mb-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($accounts as $account)
            @php $balance = $balances[$account->id] ?? 0; @endphp
            <section class="card card-hover relative overflow-hidden p-5 {{ $account->is_active ? '' : 'opacity-60' }}">
                <div class="flex items-start justify-between gap-3">
                    <span class="grid h-11 w-11 place-items-center rounded-xl text-white shadow-lg" style="background: {{ $account->color ?: '#0f172a' }}">
                        <x-icon :name="$account->counts_as_savings ? 'savings' : 'wallet'" class="h-5 w-5" />
                    </span>
                    <div class="flex gap-1">
                        <button class="btn-icon h-8 w-8" wire:click="viewLedger({{ $account->id }})" title="View ledger" aria-label="View ledger">
                            <x-icon name="reports" class="h-3.5 w-3.5" />
                        </button>
                        <button class="btn-icon h-8 w-8" wire:click="openEdit({{ $account->id }})" title="Edit" aria-label="Edit">
                            <x-icon name="pencil" class="h-3.5 w-3.5" />
                        </button>
                        <button class="btn-icon h-8 w-8 text-rose-500" wire:click="toggleActive({{ $account->id }})" title="{{ $account->is_active ? 'Archive' : 'Activate' }}" aria-label="Toggle active">
                            <x-icon :name="$account->is_active ? 'x' : 'check'" class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ $account->name }}</h2>
                        @if ($account->counts_as_savings)
                            <span class="chip bg-emerald-400/10 text-emerald-600 dark:text-emerald-300">Savings</span>
                        @endif
                        @if (! $account->is_active)
                            <span class="chip bg-slate-400/10 text-slate-500">Archived</span>
                        @endif
                    </div>
                    <p class="text-[10px] uppercase tracking-wider text-slate-400">
                        {{ $account->institution ? $account->institution.' · ' : '' }}{{ $account->typeLabel() }}
                    </p>
                    <p class="mt-3 text-2xl font-extrabold tnum {{ $balance < 0 ? 'text-rose-500' : 'text-slate-900 dark:text-white' }}">
                        {{ \App\Support\Money::format($balance) }}
                    </p>
                    <p class="mt-1 text-[10px] text-slate-400">Opening: {{ \App\Support\Money::format($account->opening_balance) }}</p>
                </div>

                @if ($account->notes)
                    <p class="mt-3 line-clamp-2 text-[11px] text-slate-400">{{ $account->notes }}</p>
                @endif
            </section>
        @endforeach
    </div>

    <section class="card rise overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 p-5 dark:border-white/[0.06]">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Recent transfers</h2>
                <p class="text-xs text-slate-400">Moving money between your own accounts</p>
            </div>
            <button wire:click="openTransfer" class="btn-ghost px-3 py-1.5 text-xs">New transfer</button>
        </div>

        @if ($transfers->count())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-100 dark:border-white/[0.06]">
                        <tr>
                            <th class="table-head px-5 py-3">Date</th>
                            <th class="table-head px-5 py-3">From</th>
                            <th class="table-head px-5 py-3">To</th>
                            <th class="table-head px-5 py-3">Goal</th>
                            <th class="table-head px-5 py-3 text-right">Amount</th>
                            <th class="table-head px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/[0.06]">
                        @foreach ($transfers as $transfer)
                            <tr class="group hover:bg-slate-50/60 dark:hover:bg-white/[0.02]">
                                <td class="whitespace-nowrap px-5 py-3 text-xs text-slate-500 dark:text-slate-400">{{ $transfer->transferred_on->format('M j, Y') }}</td>
                                <td class="px-5 py-3 text-xs text-slate-500 dark:text-slate-300">{{ $transfer->fromAccount?->name }}</td>
                                <td class="px-5 py-3 text-xs text-slate-500 dark:text-slate-300">{{ $transfer->toAccount?->name }}</td>
                                <td class="px-5 py-3 text-xs">
                                    @if ($transfer->goal)
                                        <span class="chip bg-emerald-400/10 text-emerald-600 dark:text-emerald-300">{{ $transfer->goal->name }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right font-semibold tnum text-slate-800 dark:text-slate-100">{{ \App\Support\Money::format($transfer->amount) }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex justify-end opacity-100 md:opacity-0 md:group-hover:opacity-100">
                                        <button class="btn-icon h-8 w-8 text-rose-500" wire:click="deleteTransfer({{ $transfer->id }})" wire:confirm="Delete this transfer?" aria-label="Delete transfer">
                                            <x-icon name="trash" class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="p-6 text-center text-sm text-slate-400">No transfers yet. Move money to savings to build the habit.</p>
        @endif
    </section>

    {{-- Account form --}}
    @if ($showAccountForm)
        <div class="fixed inset-0 z-[90] flex items-end justify-center p-3 sm:items-center sm:p-6">
            <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" wire:click="$set('showAccountForm', false)"></div>
            <div class="card modal-pop relative max-h-[90vh] w-full max-w-lg overflow-y-auto p-5 sm:p-6">
                <div class="mb-5 flex items-start justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-500">{{ $accountId ? 'Edit' : 'New' }}</p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ $accountId ? 'Update account' : 'Add account' }}</h2>
                    </div>
                    <button class="btn-icon h-8 w-8" wire:click="$set('showAccountForm', false)" aria-label="Close"><x-icon name="x" class="h-4 w-4" /></button>
                </div>

                <form wire:submit="saveAccount" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Account name</label>
                            <input type="text" wire:model="name" class="input" placeholder="BPI Checking">
                            @error('name') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Bank / provider <span class="normal-case tracking-normal text-slate-400">(optional)</span></label>
                            <input type="text" wire:model="institution" class="input" placeholder="BPI, GCash, Cash...">
                            @error('institution') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Type</label>
                            <select wire:model.live="type" class="input">
                                <option value="checking">Checking</option>
                                <option value="savings">Savings</option>
                                <option value="cash">Cash</option>
                                <option value="e_wallet">E-wallet</option>
                                <option value="investment">Investment</option>
                            </select>
                            @error('type') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Opening balance</label>
                            <input type="number" step="0.01" wire:model="opening_balance" class="input tnum">
                            @error('opening_balance') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <label class="flex items-start gap-2 rounded-xl border border-emerald-500/15 bg-emerald-500/[0.05] p-3 text-xs text-slate-600 dark:text-slate-300">
                        <input type="checkbox" wire:model="counts_as_savings" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-500 focus:ring-emerald-400 dark:border-white/20 dark:bg-white/5">
                        <span>Count this account as savings. Transfers into it count toward your savings record and goals.</span>
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Accent color</label>
                            <input type="color" wire:model="color" class="h-10 w-full cursor-pointer rounded-xl border border-slate-200 bg-white p-1 dark:border-white/10 dark:bg-white/5">
                        </div>
                        <label class="flex items-center gap-2 pt-5 text-xs text-slate-500 dark:text-slate-400">
                            <input type="checkbox" wire:model="is_active" class="h-4 w-4 rounded border-slate-300 text-emerald-500 focus:ring-emerald-400 dark:border-white/20 dark:bg-white/5">
                            Active account
                        </label>
                    </div>

                    <div class="flex gap-2 pt-1">
                        <button type="button" wire:click="$set('showAccountForm', false)" class="btn-ghost flex-1">Cancel</button>
                        <button type="submit" class="btn-primary flex-1" wire:loading.attr="disabled" wire:target="saveAccount">
                            <span wire:loading.remove wire:target="saveAccount">{{ $accountId ? 'Save changes' : 'Add account' }}</span>
                            <span wire:loading wire:target="saveAccount">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Transfer form --}}
    @if ($showTransferForm)
        <div class="fixed inset-0 z-[90] flex items-end justify-center p-3 sm:items-center sm:p-6">
            <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" wire:click="$set('showTransferForm', false)"></div>
            <div class="card modal-pop relative w-full max-w-md p-5 sm:p-6">
                <div class="mb-5 flex items-start justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-500">Move money</p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">New transfer</h2>
                    </div>
                    <button class="btn-icon h-8 w-8" wire:click="$set('showTransferForm', false)" aria-label="Close"><x-icon name="x" class="h-4 w-4" /></button>
                </div>

                <form wire:submit="saveTransfer" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">From</label>
                            <select wire:model="from_account_id" class="input">
                                @foreach ($accounts->where('is_active', true) as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                            @error('from_account_id') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">To</label>
                            <select wire:model="to_account_id" class="input">
                                @foreach ($accounts->where('is_active', true) as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                            @error('to_account_id') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Amount</label>
                            <input type="number" step="0.01" min="0.01" wire:model="amount" class="input tnum text-lg font-bold">
                            @error('amount') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Date</label>
                            <input type="date" wire:model="transferred_on" max="{{ now()->toDateString() }}" class="input">
                            @error('transferred_on') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="label">Goal <span class="normal-case tracking-normal text-slate-400">(optional)</span></label>
                        <select wire:model="goal_id" class="input">
                            <option value="">No goal</option>
                            @foreach ($goals as $goal)
                                <option value="{{ $goal->id }}">{{ $goal->name }}</option>
                            @endforeach
                        </select>
                        @error('goal_id') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Note <span class="normal-case tracking-normal text-slate-400">(optional)</span></label>
                        <input type="text" wire:model="transfer_note" class="input" placeholder="Payday savings, goal top-up...">
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button type="button" wire:click="$set('showTransferForm', false)" class="btn-ghost flex-1">Cancel</button>
                        <button type="submit" class="btn-primary flex-1" wire:loading.attr="disabled" wire:target="saveTransfer">
                            <span wire:loading.remove wire:target="saveTransfer">Move money</span>
                            <span wire:loading wire:target="saveTransfer">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Ledger --}}
    @if ($viewing)
        <div class="fixed inset-0 z-[90] flex items-end justify-center p-3 sm:items-center sm:p-6">
            <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" wire:click="closeLedger"></div>
            <div class="card modal-pop relative max-h-[90vh] w-full max-w-2xl overflow-y-auto p-5 sm:p-6">
                <div class="mb-5 flex items-start justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-500">Ledger</p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ $viewing->name }}</h2>
                        <p class="text-xs text-slate-400">Computed balance · {{ \App\Support\Money::format($balances[$viewing->id] ?? 0) }}</p>
                    </div>
                    <button class="btn-icon h-8 w-8" wire:click="closeLedger" aria-label="Close"><x-icon name="x" class="h-4 w-4" /></button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-slate-100 dark:border-white/[0.06]">
                            <tr>
                                <th class="table-head px-2 py-2">Date</th>
                                <th class="table-head px-2 py-2">Description</th>
                                <th class="table-head px-2 py-2 text-right">Amount</th>
                                <th class="table-head px-2 py-2 text-right">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/[0.06]">
                            @forelse ($ledger as $entry)
                                <tr>
                                    <td class="whitespace-nowrap px-2 py-2.5 text-[11px] text-slate-400">
                                        {{ $entry['type'] === 'opening' ? '—' : $entry['date']->format('M j, Y') }}
                                    </td>
                                    <td class="px-2 py-2.5">
                                        <span class="text-xs font-medium text-slate-700 dark:text-slate-200">{{ $entry['label'] }}</span>
                                        <span class="ml-1 text-[9px] uppercase tracking-wider text-slate-400">{{ $entry['type'] }}</span>
                                    </td>
                                    <td class="px-2 py-2.5 text-right text-xs font-semibold tnum {{ $entry['amount'] >= 0 ? 'text-emerald-500' : 'text-rose-500' }}">
                                        {{ $entry['amount'] >= 0 ? '+' : '−' }}{{ \App\Support\Money::format(abs($entry['amount'])) }}
                                    </td>
                                    <td class="px-2 py-2.5 text-right text-xs font-semibold tnum text-slate-700 dark:text-slate-200">{{ \App\Support\Money::format($entry['balance']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-2 py-8 text-center text-sm text-slate-400">No movements on this account yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
