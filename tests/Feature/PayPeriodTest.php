<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Category;
use App\Models\Payday;
use App\Models\Account;
use App\Services\PayPeriodService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayPeriodTest extends TestCase
{
    use RefreshDatabase;

    private function service(): PayPeriodService
    {
        return app(PayPeriodService::class);
    }

    public function test_next_scheduled_date_handles_regular_months(): void
    {
        $service = $this->service();

        $this->assertSame('2026-08-15', $service->nextScheduledDateAfter(Carbon::parse('2026-08-01'))->toDateString());
        $this->assertSame('2026-08-31', $service->nextScheduledDateAfter(Carbon::parse('2026-08-15'))->toDateString());
        $this->assertSame('2026-08-31', $service->nextScheduledDateAfter(Carbon::parse('2026-08-16'))->toDateString());
        $this->assertSame('2026-09-15', $service->nextScheduledDateAfter(Carbon::parse('2026-08-31'))->toDateString());
    }

    public function test_next_scheduled_date_handles_february_and_leap_years(): void
    {
        $service = $this->service();

        $this->assertSame('2026-02-15', $service->nextScheduledDateAfter(Carbon::parse('2026-02-01'))->toDateString());
        $this->assertSame('2026-02-28', $service->nextScheduledDateAfter(Carbon::parse('2026-02-15'))->toDateString());
        $this->assertSame('2026-03-15', $service->nextScheduledDateAfter(Carbon::parse('2026-02-28'))->toDateString());

        $this->assertSame('2028-02-15', $service->nextScheduledDateAfter(Carbon::parse('2028-02-01'))->toDateString());
        $this->assertSame('2028-02-29', $service->nextScheduledDateAfter(Carbon::parse('2028-02-15'))->toDateString());
        $this->assertSame('2028-03-15', $service->nextScheduledDateAfter(Carbon::parse('2028-02-29'))->toDateString());
    }

    public function test_next_scheduled_date_crosses_the_year_boundary(): void
    {
        $this->assertSame(
            '2027-01-15',
            $this->service()->nextScheduledDateAfter(Carbon::parse('2026-12-31'))->toDateString()
        );
    }

    public function test_periods_match_the_spec_examples(): void
    {
        $account = Account::create([
            'name' => 'Checking', 'type' => 'checking', 'opening_balance' => 0,
        ]);

        $aug15 = Payday::create(['pay_date' => '2026-08-15', 'net_amount' => 20000, 'account_id' => $account->id]);
        $aug31 = Payday::create(['pay_date' => '2026-08-31', 'net_amount' => 20000, 'account_id' => $account->id]);
        $feb15 = Payday::create(['pay_date' => '2026-02-15', 'net_amount' => 20000, 'account_id' => $account->id]);
        $feb28 = Payday::create(['pay_date' => '2026-02-28', 'net_amount' => 20000, 'account_id' => $account->id]);

        $period = $this->service()->periodForPayday($aug15);
        $this->assertSame('2026-08-15', $period->start->toDateString());
        $this->assertSame('2026-08-30', $period->end->toDateString());
        $this->assertSame(16, $period->days());
        $this->assertSame('Aug 15–30', $period->label());

        $period = $this->service()->periodForPayday($aug31);
        $this->assertSame('2026-08-31', $period->start->toDateString());
        $this->assertSame('2026-09-14', $period->end->toDateString());
        $this->assertSame(15, $period->days());

        $period = $this->service()->periodForPayday($feb15);
        $this->assertSame('2026-02-27', $period->end->toDateString());

        $period = $this->service()->periodForPayday($feb28);
        $this->assertSame('2026-03-14', $period->end->toDateString());
    }

    public function test_bill_due_day_31_clamps_to_the_last_day_of_february(): void
    {
        $category = Category::create(['name' => 'Bills', 'group' => 'bills']);

        $bill = Bill::create([
            'name' => 'Card', 'amount' => 1000, 'category_id' => $category->id, 'due_day' => 31,
        ]);

        $this->assertSame('2026-02-28', $bill->dueDateInMonth(Carbon::parse('2026-02-01'))->toDateString());
        $this->assertSame('2028-02-29', $bill->dueDateInMonth(Carbon::parse('2028-02-01'))->toDateString());
        $this->assertSame('2026-04-30', $bill->dueDateInMonth(Carbon::parse('2026-04-01'))->toDateString());
    }

    public function test_expense_on_a_payday_belongs_to_the_new_period(): void
    {
        $account = Account::create(['name' => 'Checking', 'type' => 'checking', 'opening_balance' => 0]);

        Payday::create(['pay_date' => '2026-07-31', 'net_amount' => 20000, 'account_id' => $account->id]);
        Payday::create(['pay_date' => '2026-08-15', 'net_amount' => 20000, 'account_id' => $account->id]);

        $onPayday = $this->service()->periodForDate(Carbon::parse('2026-08-15'));
        $beforePayday = $this->service()->periodForDate(Carbon::parse('2026-08-14'));

        $this->assertSame('2026-08-15', $onPayday->start->toDateString());
        $this->assertSame('2026-07-31', $beforePayday->start->toDateString());
        $this->assertSame('2026-08-14', $beforePayday->end->toDateString());
    }
}
