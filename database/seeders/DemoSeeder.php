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

class DemoSeeder extends Seeder
{
    /**
     * The exact acceptance scenario from docs/SPEC.md §8 (August 2026).
     * Intended for a fresh database, automated tests, and manual verification.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('SEED_EMAIL', 'me@budgetdesk.local')],
            [
                'name' => env('SEED_NAME', 'Budget Keeper'),
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

        $checking = Account::updateOrCreate(['name' => 'BPI Checking'], [
            'institution' => 'BPI',
            'type' => 'checking',
            'counts_as_savings' => false,
            'opening_balance' => 5000,
            'color' => '#0f172a',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $savings = Account::updateOrCreate(['name' => 'BPI Savings'], [
            'institution' => 'BPI',
            'type' => 'savings',
            'counts_as_savings' => true,
            'opening_balance' => 0,
            'color' => '#34d399',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $categories = collect([
            ['name' => 'Food', 'group' => 'needs', 'color' => '#34d399'],
            ['name' => 'Transport', 'group' => 'needs', 'color' => '#22d3ee'],
            ['name' => 'Bills', 'group' => 'bills', 'color' => '#fbbf24'],
            ['name' => 'Rent', 'group' => 'bills', 'color' => '#fb7185'],
            ['name' => 'Entertainment', 'group' => 'wants', 'color' => '#a78bfa'],
        ])->mapWithKeys(function (array $row) {
            $category = Category::updateOrCreate(['name' => $row['name']], $row + [
                'default_monthly_budget' => null,
                'is_active' => true,
            ]);

            return [$row['name'] => $category];
        });

        $rent = Bill::updateOrCreate(['name' => 'Rent'], [
            'amount' => 8000,
            'category_id' => $categories['Rent']->id,
            'account_id' => $checking->id,
            'due_day' => 5,
            'is_active' => true,
        ]);

        $internet = Bill::updateOrCreate(['name' => 'Internet'], [
            'amount' => 1500,
            'category_id' => $categories['Bills']->id,
            'account_id' => $checking->id,
            'due_day' => 20,
            'is_active' => true,
        ]);

        $electricity = Bill::updateOrCreate(['name' => 'Electricity'], [
            'amount' => 2000,
            'category_id' => $categories['Bills']->id,
            'account_id' => $checking->id,
            'due_day' => 25,
            'is_active' => true,
        ]);

        $emergency = SavingsGoal::updateOrCreate(['name' => 'Emergency Fund'], [
            'target_amount' => 54000,
            'target_date' => null,
            'is_emergency_fund' => true,
            'color' => '#fbbf24',
            'is_active' => true,
        ]);

        $budgets = [
            'Rent' => 8000,
            'Food' => 5000,
            'Transport' => 2000,
            'Bills' => 11500,
        ];

        foreach ($budgets as $name => $amount) {
            Budget::updateOrCreate(
                ['category_id' => $categories[$name]->id, 'month' => '2026-08'],
                ['amount' => $amount]
            );
        }

        Payday::updateOrCreate(['pay_date' => '2026-08-15'], [
            'gross_amount' => 20000,
            'net_amount' => 20000,
            'account_id' => $checking->id,
            'notes' => 'Semi-monthly salary',
        ]);

        Payday::updateOrCreate(['pay_date' => '2026-08-31'], [
            'gross_amount' => 20000,
            'net_amount' => 20000,
            'account_id' => $checking->id,
            'notes' => 'Semi-monthly salary',
        ]);

        $expenses = [
            ['date' => '2026-08-16', 'amount' => 1200, 'category' => 'Food', 'account' => $checking, 'note' => 'Groceries', 'bill' => null],
            ['date' => '2026-08-18', 'amount' => 350, 'category' => 'Transport', 'account' => $checking, 'note' => 'Ride', 'bill' => null],
            ['date' => '2026-08-20', 'amount' => 1500, 'category' => 'Bills', 'account' => $checking, 'note' => 'Internet bill payment', 'bill' => $internet],
            ['date' => '2026-08-25', 'amount' => 2300, 'category' => 'Bills', 'account' => $checking, 'note' => 'Electricity bill payment', 'bill' => $electricity],
            ['date' => '2026-08-28', 'amount' => 900, 'category' => 'Entertainment', 'account' => $checking, 'note' => 'Dinner', 'bill' => null],
            ['date' => '2026-09-05', 'amount' => 8000, 'category' => 'Rent', 'account' => $checking, 'note' => 'Rent payment', 'bill' => $rent],
        ];

        foreach ($expenses as $row) {
            Expense::firstOrCreate(
                ['spent_on' => $row['date'], 'note' => $row['note']],
                [
                    'amount' => $row['amount'],
                    'category_id' => $categories[$row['category']]->id,
                    'account_id' => $row['account']->id,
                    'bill_id' => $row['bill']?->id,
                ]
            );
        }

        Transfer::firstOrCreate(
            ['transferred_on' => '2026-08-15', 'from_account_id' => $checking->id, 'to_account_id' => $savings->id, 'amount' => 4000],
            ['goal_id' => $emergency->id, 'note' => 'Payday savings']
        );

        Transfer::firstOrCreate(
            ['transferred_on' => '2026-08-31', 'from_account_id' => $checking->id, 'to_account_id' => $savings->id, 'amount' => 3500],
            ['goal_id' => $emergency->id, 'note' => 'Payday savings (short by 500)']
        );
    }
}
