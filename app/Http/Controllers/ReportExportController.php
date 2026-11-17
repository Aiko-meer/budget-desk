<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Bill;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Payday;
use App\Models\SavingsGoal;
use App\Models\Settings;
use App\Models\Transfer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function csv(Request $request): StreamedResponse
    {
        $month = $request->query('month', now()->format('Y-m'));

        $expenses = Expense::with(['category', 'account', 'bill'])
            ->forMonth($month)
            ->orderBy('spent_on')
            ->get();

        $filename = 'expenses-'.$month.'.csv';

        return response()->streamDownload(function () use ($expenses) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Date', 'Category', 'Account', 'Note', 'Amount', 'Bill']);

            foreach ($expenses as $expense) {
                fputcsv($out, [
                    $expense->spent_on->toDateString(),
                    $expense->category?->name,
                    $expense->account?->name,
                    $expense->note,
                    number_format((float) $expense->amount, 2, '.', ''),
                    $expense->bill?->name,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function json(Request $request): StreamedResponse
    {
        $payload = [
            'exported_at' => now()->toIso8601String(),
            'app' => 'Budget Desk',
            'settings' => Settings::query()->first()?->toArray(),
            'accounts' => Account::query()->orderBy('id')->get()->toArray(),
            'categories' => Category::query()->orderBy('id')->get()->toArray(),
            'savings_goals' => SavingsGoal::query()->orderBy('id')->get()->toArray(),
            'bills' => Bill::query()->orderBy('id')->get()->toArray(),
            'budgets' => Budget::query()->orderBy('id')->get()->toArray(),
            'paydays' => Payday::query()->orderBy('id')->get()->toArray(),
            'expenses' => Expense::query()->orderBy('id')->get()->toArray(),
            'transfers' => Transfer::query()->orderBy('id')->get()->toArray(),
        ];

        $filename = 'budget-desk-export-'.now()->format('Y-m-d-His').'.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $filename, ['Content-Type' => 'application/json']);
    }
}
