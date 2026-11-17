<div>
    <button
        type="button"
        wire:click="open"
        class="group fixed bottom-6 right-6 z-40 grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br from-emerald-300 via-emerald-400 to-cyan-400 text-slate-950 shadow-2xl shadow-emerald-500/30 transition duration-300 hover:scale-105 active:scale-95"
        aria-label="Add expense"
    >
        <span class="absolute -inset-1 -z-10 rounded-3xl bg-emerald-400/30 blur-xl transition group-hover:bg-emerald-400/50"></span>
        <x-icon name="plus" class="h-6 w-6" />
    </button>

    @if ($show)
        <div class="fixed inset-0 z-[90] flex items-end justify-center p-3 sm:items-center sm:p-6">
            <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" wire:click="close"></div>

            <div class="card modal-pop relative w-full max-w-md overflow-hidden p-5 sm:p-6">
                <div class="pointer-events-none absolute -right-16 -top-20 h-48 w-48 rounded-full bg-emerald-400/20 blur-3xl"></div>

                <div class="relative mb-5 flex items-start justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-500">Quick add</p>
                        <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">New expense</h2>
                    </div>
                    <button class="btn-icon h-8 w-8" wire:click="close" aria-label="Close">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <form wire:submit="save" class="relative space-y-4">
                    <div>
                        <label class="label" for="quick-amount">Amount</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">{{ \App\Models\Settings::current()->currency_symbol }}</span>
                            <input id="quick-amount" type="number" step="0.01" min="0.01" wire:model="amount" class="input pl-9 text-lg font-bold tnum" placeholder="0.00" autofocus>
                        </div>
                        @error('amount') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label" for="quick-category">Category</label>
                            <select id="quick-category" wire:model="category_id" class="input">
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="quick-account">Account</label>
                            <select id="quick-account" wire:model="account_id" class="input">
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                            @error('account_id') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="label" for="quick-date">Date</label>
                        <input id="quick-date" type="date" wire:model="spent_on" max="{{ now()->toDateString() }}" class="input">
                        @error('spent_on') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label" for="quick-note">Note <span class="normal-case tracking-normal text-slate-400">(optional)</span></label>
                        <input id="quick-note" type="text" wire:model="note" class="input" placeholder="Lunch, jeepney, coffee...">
                        @error('note') <p class="mt-1.5 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex gap-2 pt-1">
                        <button type="button" wire:click="close" class="btn-ghost flex-1">Cancel</button>
                        <button type="submit" class="btn-primary flex-1" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">Save expense</span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
