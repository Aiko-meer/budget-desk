<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Bill;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Payday;
use App\Models\SavingsGoal;
use App\Models\Settings;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed a single local user and a realistic starter dataset
     * relative to today so the dashboard is alive on first login.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('SEED_EMAIL', 'me@budgetdesk.local')],
            [
                'name' => env('SEED_NAME', 'Alex'),
                'password' => Hash::make(env('SEED_PASSWORD', 'password')),
            ]
        );

        Settings::updateOrCreate(['id' => 1], [
            'currency_code' => 'PHP',
            'currency_symbol' => '₱',
            'savings_rate' => 20,
            'savings_rounding' => 100,
            'emergency_fund_months' => 3,
            'timezone' => 'Asia/Manila',
        ]);

        $accounts = collect([
            ['name' => 'BPI Checking', 'institution' => 'BPI', 'type' => 'checking', 'counts_as_savings' => false, 'opening_balance' => 5000, 'color' => '#0f172a', 'sort_order' => 1, 'notes' => 'Salary lands here.'],
            ['name' => 'BPI Savings', 'institution' => 'BPI', 'type' => 'savings', 'counts_as_savings' => true, 'opening_balance' => 0, 'color' => '#34d399', 'sort_order' => 2, 'notes' => 'Payday savings go here.'],
            ['name' => 'GCash', 'institution' => 'GCash', 'type' => 'e_wallet', 'counts_as_savings' => false, 'opening_balance' => 1500, 'color' => '#22d3ee', 'sort_order' => 3, 'notes' => 'Everyday digital wallet.'],
            ['name' => 'Cash Wallet', 'institution' => null, 'type' => 'cash', 'counts_as_savings' => false, 'opening_balance' => 800, 'color' => '#fbbf24', 'sort_order' => 4, 'notes' => 'Pocket money.'],
        ])->mapWithKeys(function (array $row) {
            $account = Account::updateOrCreate(['name' => $row['name']], $row + ['is_active' => true]);

            return [$row['name'] => $account];
        });

        $categories = collect([
            ['name' => 'Food', 'group' => 'needs', 'color' => '#34d399', 'default_monthly_budget' => 6000, 'sort_order' => 1],
            ['name' => 'Transport', 'group' => 'needs', 'color' => '#22d3ee', 'default_monthly_budget' => 2500, 'sort_order' => 2],
            ['name' => 'Bills', 'group' => 'bills', 'color' => '#fbbf24', 'default_monthly_budget' => 11500, 'sort_order' => 3],
            ['name' => 'Rent', 'group' => 'bills', 'color' => '#fb7185', 'default_monthly_budget' => 8000, 'sort_order' => 4],
            ['name' => 'Entertainment', 'group' => 'wants', 'color' => '#a78bfa', 'default_monthly_budget' => 3000, 'sort_order' => 5],
            ['name' => 'Shopping', 'group' => 'wants', 'color' => '#60a5fa', 'default_monthly_budget' => 2500, 'sort_order' => 6],
            ['name' => 'Health', 'group' => 'needs', 'color' => '#f472b6', 'default_monthly_budget' => 1500, 'sort_order' => 7],
            ['name' => 'Other', 'group' => 'other', 'color' => '#94a3b8', 'default_monthly_budget' => 0, 'sort_order' => 8],
        ])->mapWithKeys(function (array $row) {
            $category = Category::updateOrCreate(['name' => $row['name']], $row + ['is_active' => true]);

            return [$row['name'] => $category];
        });

        $bills = collect([
            ['name' => 'Rent', 'amount' => 8000, 'category' => 'Rent', 'account' => 'BPI Checking', 'due_day' => 5, 'notes' => 'Monthly rent.'],
            ['name' => 'Internet', 'amount' => 1500, 'category' => 'Bills', 'account' => 'BPI Checking', 'due_day' => 20, 'notes' => 'Fiber plan.'],
            ['name' => 'Electricity', 'amount' => 2000, 'category' => 'Bills', 'account' => 'BPI Checking', 'due_day' => 25, 'notes' => 'Meralco.'],
        ])->mapWithKeys(function (array $row) use ($categories, $accounts) {
            $bill = Bill::updateOrCreate(['name' => $row['name']], [
                'amount' => $row['amount'],
                'category_id' => $categories[$row['category']]->id,
                'account_id' => $accounts[$row['account']]->id,
                'due_day' => $row['due_day'],
                'is_active' => true,
                'notes' => $row['notes'],
            ]);

            return [$row['name'] => $bill];
        });

        SavingsGoal::updateOrCreate(['name' => 'Emergency Fund'], [
            'target_amount' => null,
            'target_date' => null,
            'is_emergency_fund' => true,
            'color' => '#fbbf24',
            'is_active' => true,
            'notes' => 'Three months of expenses, auto-calculated.',
        ]);

        SavingsGoal::updateOrCreate(['name' => 'Japan Trip'], [
            'target_amount' => 60000,
            'target_date' => now()->addMonthsNoOverflow(10)->endOfMonth()->toDateString(),
            'is_emergency_fund' => false,
            'color' => '#22d3ee',
            'is_active' => true,
            'notes' => 'Flights, rail pass, and food.',
        ]);

        SavingsGoal::updateOrCreate(['name' => 'New Laptop'], [
            'target_amount' => 45000,
            'target_date' => now()->addMonthsNoOverflow(6)->endOfMonth()->toDateString(),
            'is_emergency_fund' => false,
            'color' => '#a78bfa',
            'is_active' => true,
            'notes' => 'A machine that keeps up.',
        ]);

        // Budgets for this month and the previous two.
        $monthStarts = collect([0, 1, 2])->map(fn (int $back) => now()->startOfMonth()->subMonthsNoOverflow($back)->startOfMonth());
        foreach ($monthStarts as $monthStart) {
            foreach ($categories as $category) {
                if ((float) $category->default_monthly_budget <= 0) {
                    continue;
                }

                Budget::updateOrCreate(
                    ['category_id' => $category->id, 'month' => $monthStart->format('Y-m')],
                    ['amount' => $category->default_monthly_budget]
                );
            }
        }

        // Paydays on the 15th and the last day for the last four months.
        $paydays = collect();
        foreach ([3, 2, 1, 0] as $back) {
            $month = now()->startOfMonth()->subMonthsNoOverflow($back);
            foreach ([$month->copy()->day(15), $month->copy()->endOfMonth()] as $date) {
                if ($date->isFuture()) {
                    continue;
                }

                $paydays->push(Payday::updateOrCreate(
                    ['pay_date' => $date->toDateString()],
                    [
                        'gross_amount' => 22000,
                        'net_amount' => 20000,
                        'account_id' => $accounts['BPI Checking']->id,
                        'notes' => 'Semi-monthly salary',
                    ]
                ));
            }
        }

        $paydays = $paydays->sortBy('pay_date')->values();

        if (Transfer::count() === 0) {
            $emergency = SavingsGoal::where('name', 'Emergency Fund')->first();
            $japan = SavingsGoal::where('name', 'Japan Trip')->first();
            $laptop = SavingsGoal::where('name', 'New Laptop')->first();

            foreach ($paydays as $index => $payday) {
                // One earlier payday is intentionally short to show the "short" state.
                $amount = $index === max(0, $paydays->count() - 2) ? 3500 : 4000;

                Transfer::create([
                    'transferred_on' => $payday->pay_date->toDateString(),
                    'from_account_id' => $accounts['BPI Checking']->id,
                    'to_account_id' => $accounts['BPI Savings']->id,
                    'amount' => $amount,
                    'goal_id' => $emergency?->id,
                    'note' => 'Payday savings',
                ]);
            }

            if ($paydays->count() >= 3) {
                Transfer::create([
                    'transferred_on' => $paydays[2]->pay_date->toDateString(),
                    'from_account_id' => $accounts['BPI Checking']->id,
                    'to_account_id' => $accounts['BPI Savings']->id,
                    'amount' => 2000,
                    'goal_id' => $japan?->id,
                    'note' => 'Japan trip top-up.',
                ]);
            }

            if ($paydays->count() >= 5) {
                Transfer::create([
                    'transferred_on' => $paydays[4]->pay_date->toDateString(),
                    'from_account_id' => $accounts['BPI Checking']->id,
                    'to_account_id' => $accounts['BPI Savings']->id,
                    'amount' => 1500,
                    'goal_id' => $laptop?->id,
                    'note' => 'Laptop fund.',
                ]);
            }
        }

        if (! Transfer::where('note', 'Wallet top-up')->exists()) {
            foreach ($monthStarts as $monthStart) {
                $date = $monthStart->copy()->day(2)->startOfDay();

                if ($date->isFuture()) {
                    continue;
                }

                Transfer::create([
                    'transferred_on' => $date->toDateString(),
                    'from_account_id' => $accounts['BPI Checking']->id,
                    'to_account_id' => $accounts['GCash']->id,
                    'amount' => 3000,
                    'note' => 'Wallet top-up',
                ]);

                Transfer::create([
                    'transferred_on' => $date->toDateString(),
                    'from_account_id' => $accounts['BPI Checking']->id,
                    'to_account_id' => $accounts['Cash Wallet']->id,
                    'amount' => 2000,
                    'note' => 'Wallet top-up',
                ]);
            }
        }

        if (Expense::count() === 0) {
            $templates = [
                ['day' => 3, 'category' => 'Food', 'account' => 'BPI Checking', 'amount' => 1450, 'note' => 'Groceries'],
                ['day' => 6, 'category' => 'Transport', 'account' => 'BPI Checking', 'amount' => 280, 'note' => 'Grab rides'],
                ['day' => 8, 'category' => 'Entertainment', 'account' => 'GCash', 'amount' => 650, 'note' => 'Dinner out'],
                ['day' => 11, 'category' => 'Shopping', 'account' => 'GCash', 'amount' => 1200, 'note' => 'Household items'],
                ['day' => 13, 'category' => 'Food', 'account' => 'BPI Checking', 'amount' => 980, 'note' => 'Market run'],
                ['day' => 16, 'category' => 'Health', 'account' => 'Cash Wallet', 'amount' => 480, 'note' => 'Pharmacy'],
                ['day' => 22, 'category' => 'Food', 'account' => 'BPI Checking', 'amount' => 1320, 'note' => 'Groceries'],
                ['day' => 27, 'category' => 'Transport', 'account' => 'BPI Checking', 'amount' => 600, 'note' => 'Fuel'],
            ];

            foreach ($monthStarts as $monthStart) {
                foreach ($templates as $template) {
                    $date = $monthStart->copy()->day($template['day'])->startOfDay();

                    if ($date->isFuture()) {
                        continue;
                    }

                    Expense::create([
                        'spent_on' => $date->toDateString(),
                        'amount' => $template['amount'],
                        'category_id' => $categories[$template['category']]->id,
                        'account_id' => $accounts[$template['account']]->id,
                        'note' => $template['note'],
                    ]);
                }

                // Bill payments, linked so the Bills page marks them paid.
                foreach ($bills as $bill) {
                    $date = $bill->dueDateInMonth($monthStart);

                    if ($date->isFuture()) {
                        continue;
                    }

                    Expense::create([
                        'spent_on' => $date->toDateString(),
                        'amount' => $bill->amount,
                        'category_id' => $bill->category_id,
                        'account_id' => $accounts['BPI Checking']->id,
                        'bill_id' => $bill->id,
                        'note' => $bill->name.' payment',
                    ]);
                }
            }
        }
    }
}
