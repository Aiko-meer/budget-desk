<div>
    <div class="rise mb-6">
        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-500">Preferences</p>
        <h1 class="page-title mt-1">Settings</h1>
        <p class="muted mt-1">Your savings rule, currency, and local data.</p>
    </div>

    <div class="grid gap-5 lg:grid-cols-[1.15fr_.85fr]">
        <section class="card rise p-5 sm:p-6">
            <div class="flex items-start gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-400/10 text-emerald-500">
                    <x-icon name="sparkles" class="h-5 w-5" />
                </span>
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Savings rule</h2>
                    <p class="text-xs text-slate-400">Applied to every payday in the advice card.</p>
                </div>
            </div>

            <form wire:submit="save" class="mt-6 space-y-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="savings-rate">Savings rate (% of payday)</label>
                        <input id="savings-rate" type="number" step="0.5" min="0" max="100" wire:model="savings_rate" class="input tnum">
                        @error('savings_rate') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="savings-rounding">Round target to nearest</label>
                        <input id="savings-rounding" type="number" step="10" min="0" max="10000" wire:model="savings_rounding" class="input tnum">
                        <p class="mt-1 text-[10px] text-slate-400">Set 0 for exact amounts.</p>
                        @error('savings_rounding') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/[0.06] p-4 text-xs leading-5 text-slate-600 dark:text-slate-300">
                    With a {{ number_format((float) $savings_rate, 0) }}% rule, a {{ \App\Models\Settings::current()->currency_symbol }}20,000 payday asks you to save about
                    <span class="font-bold text-emerald-600 dark:text-emerald-300">{{ \App\Support\Money::format(20000 * (float) $savings_rate / 100) }}</span>.
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="currency-code">Currency code</label>
                        <input id="currency-code" type="text" maxlength="3" wire:model="currency_code" class="input uppercase">
                        @error('currency_code') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="currency-symbol">Currency symbol</label>
                        <input id="currency-symbol" type="text" maxlength="8" wire:model="currency_symbol" class="input">
                        @error('currency_symbol') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="label" for="emergency-months">Emergency fund months</label>
                    <input id="emergency-months" type="number" min="1" max="24" wire:model="emergency_fund_months" class="input tnum">
                    <p class="mt-1 text-[10px] text-slate-400">Used to suggest your emergency fund target.</p>
                    @error('emergency_fund_months') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end pt-1">
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Save settings</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </button>
                </div>
            </form>
        </section>

        <div class="space-y-5">
            <section class="card rise p-5 sm:p-6">
                <div class="flex items-start gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-cyan-400/10 text-cyan-500">
                        <x-icon name="download" class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Your data</h2>
                        <p class="text-xs text-slate-400">Everything stays on this machine.</p>
                    </div>
                </div>

                <div class="mt-5 space-y-2">
                    <button type="button" wire:click="backupNow" class="btn-ghost w-full justify-between">
                        <span class="flex items-center gap-2"><x-icon name="download" class="h-4 w-4" /> Backup database now</span>
                        <span class="text-[10px] text-slate-400">JSON</span>
                    </button>
                    <a href="{{ route('settings.export') }}" class="btn-ghost w-full justify-between">
                        <span class="flex items-center gap-2"><x-icon name="download" class="h-4 w-4" /> Export all data</span>
                        <span class="text-[10px] text-slate-400">JSON</span>
                    </a>
                    <a href="{{ route('reports.export', ['month' => now()->format('Y-m')]) }}" class="btn-ghost w-full justify-between">
                        <span class="flex items-center gap-2"><x-icon name="expenses" class="h-4 w-4" /> Export this month's expenses</span>
                        <span class="text-[10px] text-slate-400">CSV</span>
                    </a>
                </div>

                <p class="mt-4 text-[10px] leading-4 text-slate-400">
                    Backups are written to <code class="rounded bg-slate-100 px-1 py-0.5 dark:bg-white/10">storage/app/backups</code>. Keep a copy outside this folder.
                </p>
            </section>

            <section class="card rise p-5 sm:p-6">
                <div class="flex items-start gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-violet-400/10 text-violet-500">
                        <x-icon name="lock" class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Private workspace</h2>
                        <p class="mt-1 text-xs leading-5 text-slate-400">
                            Single-user login. No registration, no cloud sync, no bank connection. This app is meant to run locally on your own machine.
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
