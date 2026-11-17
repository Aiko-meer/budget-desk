<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Bill;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Payday;
use App\Models\SavingsGoal;
use App\Models\Settings;
use App\Models\Transfer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BudgetBackup extends Command
{
    protected $signature = 'budget:backup {--keep=30 : Number of backup files to keep}';

    protected $description = 'Write a JSON snapshot of all Budget Desk data to storage/app/backups';

    public function handle(): int
    {
        $directory = storage_path('app/backups');
        File::ensureDirectoryExists($directory);

        $payload = [
            'app' => 'Budget Desk',
            'exported_at' => now()->toIso8601String(),
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

        $filename = 'budget-desk-'.now()->format('Y-m-d-His').'.json';
        File::put(
            $directory.DIRECTORY_SEPARATOR.$filename,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $files = collect(File::files($directory))
            ->filter(fn ($file) => $file->getExtension() === 'json')
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values();

        $keep = max(1, (int) $this->option('keep'));
        $files->slice($keep)->each(fn ($file) => File::delete($file->getPathname()));

        $this->info('Backup written: storage/app/backups/'.$filename);
        $this->line('Kept '.min($files->count(), $keep).' backup file(s).');

        return self::SUCCESS;
    }
}
