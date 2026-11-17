<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Bill;
use App\Models\Category;
use App\Models\Payday;
use App\Models\SavingsGoal;
use App\Models\Transfer;
use App\Services\SavingsAdvisor;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsAdvisorTest extends TestCase
{
    use RefreshDatabase;

    private function advisor(): SavingsAdvisor
    {
        return app(SavingsAdvisor::class);
    }

    private function checking(): Account
    {
        return Account::where('name', 'BPI Checking')->firstOrFail();
    }

    private function savings(): Account
    {
        return Account::where('name', 'BPI Savings')->firstOrFail();
    }

    public function test_target_rounds_half_up_to_the_nearest_hundred(): void
    {
        $this->seed(DemoSeeder::class);

        $payday = Payday::create([
            'pay_date' => '2026-09-15',
            'gross_amount' => 20050,
            'net_amount' => 20050,
            'account_id' => $this->checking()->id,
        ]);

        $advice = $this->advisor()->advise($payday);

        $this->assertSame(4010.0, $advice['raw_target']);
        $this->assertSame(4000.0, $advice['target']);
    }

    public function test_shortfall_reduces_safe_to_spend_to_zero_and_warns(): void
    {
        $this->seed(DemoSeeder::class);

        $category = Category::where('name', 'Bills')->firstOrFail();

        Bill::create([
            'name' => 'Insurance',
            'amount' => 5500,
            'category_id' => $category->id,
            'account_id' => $this->checking()->id,
            'due_day' => 18,
            'is_active' => true,
        ]);

        $payday = Payday::create([
            'pay_date' => '2026-10-15',
            'net_amount' => 10000,
            'account_id' => $this->checking()->id,
        ]);

        $advice = $this->advisor()->advise($payday);

        $this->assertSame(9000.0, $advice['bills_due']);
        $this->assertSame(2000.0, $advice['target']);
        $this->assertSame(1000.0, $advice['max_save']);
        $this->assertTrue($advice['shortfall']);
        $this->assertSame(0.0, $advice['safe_to_spend']);
        $this->assertStringContainsString('save at most', $advice['advice']);
    }

    public function test_transfers_between_savings_accounts_do_not_count_as_new_savings(): void
    {
        $this->seed(DemoSeeder::class);

        $secondSavings = Account::create([
            'name' => 'Second Savings',
            'type' => 'savings',
            'counts_as_savings' => true,
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        Transfer::create([
            'transferred_on' => '2026-09-01',
            'from_account_id' => $this->savings()->id,
            'to_account_id' => $secondSavings->id,
            'amount' => 5000,
        ]);

        $payday = Payday::whereDate('pay_date', '2026-08-31')->firstOrFail();
        $advice = $this->advisor()->advise($payday);

        $this->assertSame(3500.0, $advice['actual_saved']);
    }

    public function test_goal_withdrawal_reduces_progress_and_period_savings(): void
    {
        $this->seed(DemoSeeder::class);

        $goal = SavingsGoal::where('name', 'Emergency Fund')->firstOrFail();
        $this->assertSame(7500.0, $goal->progressAmount());
        $this->assertSame(13.9, $goal->progressPercent());

        Transfer::create([
            'transferred_on' => '2026-09-01',
            'from_account_id' => $this->savings()->id,
            'to_account_id' => $this->checking()->id,
            'amount' => 1000,
            'goal_id' => $goal->id,
            'note' => 'Emergency withdrawal',
        ]);

        $this->assertSame(6500.0, $goal->fresh()->progressAmount());
        $this->assertSame(12.0, SavingsGoal::where('name', 'Emergency Fund')->first()->progressPercent());

        $payday = Payday::whereDate('pay_date', '2026-08-31')->firstOrFail();
        $this->assertSame(2500.0, $this->advisor()->advise($payday)['actual_saved']);
    }

    public function test_statuses_met_short_and_ahead(): void
    {
        $this->seed(DemoSeeder::class);

        $aug15 = Payday::whereDate('pay_date', '2026-08-15')->firstOrFail();
        $aug31 = Payday::whereDate('pay_date', '2026-08-31')->firstOrFail();

        $first = $this->advisor()->advise($aug15);
        $this->assertSame('met', $first['status']);
        $this->assertSame(4000.0, $first['actual_saved']);
        $this->assertSame(0.0, $first['difference']);

        $second = $this->advisor()->advise($aug31);
        $this->assertSame('short', $second['status']);
        $this->assertSame(-500.0, $second['difference']);

        Transfer::create([
            'transferred_on' => '2026-09-02',
            'from_account_id' => $this->checking()->id,
            'to_account_id' => $this->savings()->id,
            'amount' => 1000,
            'goal_id' => SavingsGoal::where('name', 'Emergency Fund')->value('id'),
        ]);

        $third = $this->advisor()->advise($aug31);
        $this->assertSame('ahead', $third['status']);
        $this->assertSame(500.0, $third['difference']);
    }

    public function test_savings_percentage_defaults_to_twenty_percent_of_net(): void
    {
        $this->seed(DemoSeeder::class);

        $advice = $this->advisor()->advise(Payday::whereDate('pay_date', '2026-08-15')->firstOrFail());

        $this->assertSame(20000.0, $advice['net']);
        $this->assertSame(4000.0, $advice['target']);
        $this->assertSame(3500.0, $advice['bills_due']);
        $this->assertSame(12500.0, $advice['safe_to_spend']);
        $this->assertSame(781.25, $advice['daily']);
    }
}
