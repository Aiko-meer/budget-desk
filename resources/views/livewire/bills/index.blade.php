@php $symbol = \App\Models\Settings::current()->currency_symbol; @endphp

<div>
    <div class="rise mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-500">Recurring</p>
            <h1 class="page-title mt-1">Bills</h1>
            <p class="muted mt-1">Upcoming bills are counted against the payday that must cover them.</p>
        </div>
        <button type="button" wire:click="openCreate" class="btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> Add bill
        </button>
    </div>

    <div class="stagger mb-5 grid gap-3 sm:grid-cols-3">
        <div class="card card-hover p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Active monthly bills</p>
            <p class="mt-2 text-xl font-extrabold tnum text-slate-900 dark:text-white">{{ \App\Support\Money::format($activeMonthly, true, 0) }}</p>
            <p class="mt-1 text-[11px] text-slate-400">{{ $rows->where('bill.is_active', true)->count() }} active bills</p>
        </div>
        <div class="card card-hover p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Paid this month</p>
            <p class="mt-2 text-xl font-extrabold tnum text-slate-900 dark:text-white">{{ \App\Support\Money::format($paidThisMonth, true, 0) }}</p>
            <p class="mt-1 text-[11px] text-slate-400">{{ now()->format('F Y') }}</p>
        </div>
        <div class="card card-hover p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Next due</p>
            @php $next = $rows->firstWhere('bill.is_active', true); @endphp
            <p class="mt-2 text-xl font-extrabold tnum text-slate-900 dark:text-white">{{ $next ? $next['next_due']->format('M j') : '—' }}</p>
            <p class="mt-1 text-[11px] text-slate-400">{{ $next ? $next['bill']->name : 'No active bills' }}</p>
        </div>
    </div>

    @if ($rows->count())
        <div class="stagger grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($rows as $row)
                @php $bill = $row['bill']; @endphp
                <section class="card card-hover relative overflow-hidden p-5 {{ $bill->is_active ? '' : 'opacity-60' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="grid h-10 w-10 place-items-center rounded-xl bg-amber-400/10 text-amber-500">
                                <x-icon name="bills" class="h-5 w-5" />
                            </span>
                            <div>
                                <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ $bill->name }}</h2>
                                <p class="text-[10px] uppercase tracking-wider text-slate-400">{{ $bill->category?->name }} · day {{ $bill->due_day }}</p>
                            </div>
                        </div>
                        @if ($row['paid_this_month'])
                            <span class="chip bg-emerald-400/10 text-emerald-600 dark:text-emerald-300"><x-icon name="check" class="h-3 w-3" /> Paid</span>
                        @elseif (! $bill->is_active)
                            <span class="chip bg-slate-400/10 text-slate-500">Paused</span>
                        @else
                            <span class="chip bg-cyan-400/10 text-cyan-600 dark:text-cyan-300">Due {{ $row['next_due']->format('M j') }}</span>
                        @endif
                    </div>

                    <p class="mt-4 text-2xl font-extrabold tnum text-slate-900 dark:text-white">{{ \App\Support\Money::format($bill->amount) }}</p>
                    <p class="mt-1 text-[11px] text-slate-400">
                        {{ $bill->account?->name ? 'From '.$bill->account->name : 'No default account' }}
                        · {{ $row['paid_this_month'] ? 'paid this month' : ($row['days'] <= 0 ? 'overdue for this cycle' : $row['days'].' days to due date') }}
                    </p>

                    <div class="mt-4 flex flex-wrap gap-1.5">
                        <button wire:click="openPayment({{ $bill->id }})" class="btn-primary px-3 py-1.5 text-xs">Log payment</button>
                        <button wire:click="openEdit({{ $bill->id }})" class="btn-ghost px-3 py-1.5 text-xs">Edit</button>
                        <button wire:click="toggleActive({{ $bill->id }})" class="btn-ghost px-3 py-1.5 text-xs">{{ $bill->is_active ? 'Pause' : 'Resume' }}</button>
                        <button wire:click="delete({{ $bill->id }})" wire:confirm="Delete this bill? Past payments are kept." class="btn-danger px-3 py-1.5 text-xs">Delete</button>
                    </div>
                </section>
            @endforeach
        </div>
    @else
        <section class="card rise grid place-items-center px-6 py-16 text-center">
            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-amber-400/10 text-amber-500">
                <x-icon name="bills" class="h-7 w-7" />
            </span>
            <h3 class="mt-4 text-base font-bold text-slate-900 dark:text-white">No bills yet</h3>
            <p class="mt-1 max-w-sm text-sm text-slate-400">Add rent, internet, and utilities so the savings advice knows what must be set aside.</p>
            <button wire:click="openCreate" class="btn-primary mt-4"><x-icon name="plus" class="h-4 w-4" /> Add first bill</button>
        </section>
    @endif

    @if ($showForm)
        <div class="fixed inset-0 z-[90] flex items-end justify-center p-3 sm:items-center sm:p-6">
            <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" wire:click="$set('showForm', false)"></div>
            <div class="card modal-pop relative w-full max-w-lg p-5 sm:p-6">
                <div class="mb-5 flex items-start justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-500">{{ $editingId ? 'Edit' : 'New' }}</p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ $editingId ? 'Update bill' : 'Add bill' }}</h2>
                    </div>
                    <button class="btn-icon h-8 w-8" wire:click="$set('showForm', false)" aria-label="Close"><x-icon name="x" class="h-4 w-4" /></button>
                </div>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="label">Bill name</label>
                        <input type="text" wire:model="name" class="input" placeholder="Rent, Internet, Electricity...">
                        @error('name') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Expected amount</label>
                            <input type="number" step="0.01" min="0.01" wire:model="amount" class="input tnum">
                            @error('amount') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Due day (1–31)</label>
                            <input type="number" min="1" max="31" wire:model="due_day" class="input tnum">
                            <p class="mt-1 text-[10px] text-slate-400">31 means the last day of the month.</p>
                            @error('due_day') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
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
                            <label class="label">Default account</label>
                            <select wire:model="account_id" class="input">
                                <option value="">No default</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                            @error('account_id') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                        <input type="checkbox" wire:model="is_active" class="h-4 w-4 rounded border-slate-300 text-emerald-500 focus:ring-emerald-400 dark:border-white/20 dark:bg-white/5">
                        Active — count this bill in savings advice
                    </label>
                    <div class="flex gap-2 pt-1">
                        <button type="button" wire:click="$set('showForm', false)" class="btn-ghost flex-1">Cancel</button>
                        <button type="submit" class="btn-primary flex-1" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">{{ $editingId ? 'Save changes' : 'Add bill' }}</span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showPayment)
        <div class="fixed inset-0 z-[90] flex items-end justify-center p-3 sm:items-center sm:p-6">
            <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" wire:click="$set('showPayment', false)"></div>
            <div class="card modal-pop relative w-full max-w-md p-5 sm:p-6">
                <div class="mb-5 flex items-start justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-500">Log payment</p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">Mark bill as paid</h2>
                    </div>
                    <button class="btn-icon h-8 w-8" wire:click="$set('showPayment', false)" aria-label="Close"><x-icon name="x" class="h-4 w-4" /></button>
                </div>

                <form wire:submit="savePayment" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Amount paid</label>
                            <input type="number" step="0.01" min="0.01" wire:model="payment_amount" class="input tnum text-lg font-bold">
                            @error('payment_amount') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Paid on</label>
                            <input type="date" wire:model="payment_spent_on" class="input">
                            @error('payment_spent_on') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="label">Paid from</label>
                        <select wire:model="payment_account_id" class="input">
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                        @error('payment_account_id') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <p class="rounded-xl bg-slate-50 p-3 text-[11px] leading-5 text-slate-500 dark:bg-white/5 dark:text-slate-400">
                        This creates an expense linked to the bill, which reduces the paying account and counts as paid for the month.
                    </p>
                    <div class="flex gap-2 pt-1">
                        <button type="button" wire:click="$set('showPayment', false)" class="btn-ghost flex-1">Cancel</button>
                        <button type="submit" class="btn-primary flex-1" wire:loading.attr="disabled" wire:target="savePayment">
                            <span wire:loading.remove wire:target="savePayment">Record payment</span>
                            <span wire:loading wire:target="savePayment">Recording...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
