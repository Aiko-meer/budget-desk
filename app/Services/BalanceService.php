<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Payday;
use App\Models\Transfer;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BalanceService
{
    /**
     * @return array<int, float> account id => balance
     */
    public function all(): array
    {
        $accounts = Account::query()->get();
        $income = Payday::query()->selectRaw('account_id, SUM(net_amount) as total')->groupBy('account_id')->pluck('total', 'account_id');
        $spending = Expense::query()->selectRaw('account_id, SUM(amount) as total')->groupBy('account_id')->pluck('total', 'account_id');
        $outgoing = Transfer::query()->selectRaw('from_account_id, SUM(amount) as total')->groupBy('from_account_id')->pluck('total', 'from_account_id');
        $incoming = Transfer::query()->selectRaw('to_account_id, SUM(amount) as total')->groupBy('to_account_id')->pluck('total', 'to_account_id');

        $balances = [];

        foreach ($accounts as $account) {
            $balances[$account->id] = round(
                (float) $account->opening_balance
                + (float) ($income[$account->id] ?? 0)
                + (float) ($incoming[$account->id] ?? 0)
                - (float) ($outgoing[$account->id] ?? 0)
                - (float) ($spending[$account->id] ?? 0),
                2
            );
        }

        return $balances;
    }

    public function balance(Account $account): float
    {
        return $this->all()[$account->id] ?? 0.0;
    }

    public function totalCash(): float
    {
        $accounts = Account::query()->where('is_active', true)->get();
        $balances = $this->all();

        return round(collect($accounts)->sum(fn (Account $a) => $balances[$a->id] ?? 0), 2);
    }

    public function totalSavings(): float
    {
        $accounts = Account::query()->where('is_active', true)->where('counts_as_savings', true)->get();
        $balances = $this->all();

        return round(collect($accounts)->sum(fn (Account $a) => $balances[$a->id] ?? 0), 2);
    }

    /**
     * Running ledger for an account, oldest first.
     *
     * @return Collection<int, array{date: Carbon, type: string, label: string, amount: float, balance: float}>
     */
    public function ledger(Account $account): Collection
    {
        $rows = collect();

        foreach (Payday::where('account_id', $account->id)->get() as $payday) {
            $rows->push([
                'date' => $payday->pay_date->copy(),
                'order' => 1,
                'type' => 'income',
                'label' => 'Salary',
                'amount' => (float) $payday->net_amount,
            ]);
        }

        foreach (Expense::with('category')->where('account_id', $account->id)->get() as $expense) {
            $rows->push([
                'date' => $expense->spent_on->copy(),
                'order' => 2,
                'type' => 'expense',
                'label' => $expense->note ?: ($expense->category?->name ?? 'Expense'),
                'amount' => -(float) $expense->amount,
            ]);
        }

        foreach (Transfer::with(['fromAccount', 'toAccount'])->where('from_account_id', $account->id)->get() as $transfer) {
            $rows->push([
                'date' => $transfer->transferred_on->copy(),
                'order' => 3,
                'type' => 'transfer',
                'label' => 'To '.($transfer->toAccount?->name ?? 'account'),
                'amount' => -(float) $transfer->amount,
            ]);
        }

        foreach (Transfer::with(['fromAccount', 'toAccount'])->where('to_account_id', $account->id)->get() as $transfer) {
            $rows->push([
                'date' => $transfer->transferred_on->copy(),
                'order' => 3,
                'type' => 'transfer',
                'label' => 'From '.($transfer->fromAccount?->name ?? 'account'),
                'amount' => (float) $transfer->amount,
            ]);
        }

        $balance = (float) $account->opening_balance;
        $ledger = $rows
            ->sortBy([['date', 'asc'], ['order', 'asc']])
            ->values()
            ->map(function (array $row) use (&$balance) {
                $balance = round($balance + $row['amount'], 2);
                $row['balance'] = $balance;
                unset($row['order']);

                return $row;
            });

        if ((float) $account->opening_balance !== 0.0) {
            $ledger->prepend([
                'date' => now()->subYears(50)->startOfDay(),
                'type' => 'opening',
                'label' => 'Opening balance',
                'amount' => (float) $account->opening_balance,
                'balance' => (float) $account->opening_balance,
            ]);
        }

        return $ledger;
    }
}
