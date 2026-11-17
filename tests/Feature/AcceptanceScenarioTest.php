<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Payday;
use App\Models\SavingsGoal;
use App\Services\BalanceService;
use App\Services\ReportService;
use App\Services\SavingsAdvisor;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcceptanceScenarioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_august_payday_advice_matches_the_spec(): void
    {
        $advisor = app(SavingsAdvisor::class);

        $first = $advisor->advise(Payday::whereDate('pay_date', '2026-08-15')->firstOrFail());
        $this->assertSame(4000.0, $first['target']);
        $this->assertSame(3500.0, $first['bills_due']);
        $this->assertSame(12500.0, $first['safe_to_spend']);
        $this->assertSame(781.25, $first['daily']);
        $this->assertSame('met', $first['status']);

        $second = $advisor->advise(Payday::whereDate('pay_date', '2026-08-31')->firstOrFail());
        $this->assertSame(4000.0, $second['target']);
        $this->assertSame(8000.0, $second['bills_due']);
        $this->assertSame(8000.0, $second['safe_to_spend']);
        $this->assertSame(533.33, $second['daily']);
        $this->assertSame(3500.0, $second['actual_saved']);
        $this->assertSame('short', $second['status']);
        $this->assertSame(-500.0, $second['difference']);
    }

    public function test_august_savings_and_goal_progress_match_the_spec(): void
    {
        $summary = app(ReportService::class)->monthSummary('2026-08');
        $this->assertSame(7500.0, $summary['saved']);

        $goal = SavingsGoal::where('name', 'Emergency Fund')->firstOrFail();
        $this->assertSame(7500.0, $goal->progressAmount());
        $this->assertSame(54000.0, $goal->effectiveTarget());
        $this->assertSame(13.9, $goal->progressPercent());
    }

    public function test_balances_on_august_31_match_the_spec(): void
    {
        // The Sep 5 rent is outside August; remove it to view the Aug 31 position.
        Expense::whereDate('spent_on', '2026-09-05')->delete();

        $balances = app(BalanceService::class);

        $this->assertSame(31250.0, $balances->balance(Account::where('name', 'BPI Checking')->firstOrFail()));
        $this->assertSame(7500.0, $balances->balance(Account::where('name', 'BPI Savings')->firstOrFail()));
        $this->assertSame(38750.0, $balances->totalCash());
    }

    public function test_balances_after_september_rent_match_the_spec(): void
    {
        $balances = app(BalanceService::class);

        $this->assertSame(23250.0, $balances->balance(Account::where('name', 'BPI Checking')->firstOrFail()));
        $this->assertSame(30750.0, $balances->totalCash());
    }

    public function test_august_report_matches_the_spec(): void
    {
        $summary = app(ReportService::class)->monthSummary('2026-08');

        $this->assertSame(40000.0, $summary['income']);
        $this->assertSame(6250.0, $summary['expenses']);
        $this->assertSame(7500.0, $summary['saved']);
        $this->assertSame(26250.0, $summary['net']);
    }

    public function test_august_budget_actuals_match_the_spec(): void
    {
        $rows = collect(app(ReportService::class)->budgetRows('2026-08'))->keyBy(fn ($row) => $row['category']->name);

        $this->assertSame(1200.0, $rows['Food']['spent']);
        $this->assertSame(350.0, $rows['Transport']['spent']);
        $this->assertSame(3800.0, $rows['Bills']['spent']);
        $this->assertSame(0.0, $rows['Rent']['spent']);
        $this->assertSame(5000.0, $rows['Food']['budget']);
        $this->assertSame(11500.0, $rows['Bills']['budget']);
    }
}
