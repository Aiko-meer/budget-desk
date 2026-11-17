# Budget Desk — project rules

Private, single-user budget app. Local only. Currency defaults to PHP (₱).

## Source of truth

- `docs/SPEC.md` is binding, especially §4 (money rules), §5 (schema), §8 (acceptance numbers).
- Ask before changing the schema or a money rule; update `docs/SPEC.md` in the same change.

## Commands

```bash
php artisan serve            # http://127.0.0.1:8000
npm run dev                  # Vite dev server
npm run build                # production assets
php artisan test             # PHPUnit (Pest is not used; PHP 8.2 constraint)
php artisan migrate           # run migrations
php artisan db:seed          # starter data (relative to today)
php artisan db:seed --class=DemoSeeder   # exact SPEC §8 scenario (fresh DB)
php artisan budget:backup    # JSON snapshot -> storage/app/backups
```

Local login: `me@budgetdesk.local` / `password` (from `.env` `SEED_EMAIL` / `SEED_PASSWORD`).

## Money rules (do not break)

1. Money is `decimal(12,2)`, cast on models; never store floats.
2. Balances, budget actuals, savings totals, and goal progress are always computed on read. Never store balances.
3. A pay period starts on a payday and ends the day before the next scheduled payday (15th / last day). Calendar months are only for budgets and reports.
4. Savings = transfers from a non-savings account into an account with `counts_as_savings = true`. Savings→savings transfers never count as new savings.
5. Savings target = `round(net × rate / 100 / step) × step` (default step ₱100).
6. Bills due inside the pay period reduce safe-to-spend. Warn when bills leave less than the target.
7. Transfers are the only way to move money between accounts.
8. No destructive data operations without explicit confirmation.

## UI

- Blade + Livewire 3 + Tailwind 4. Dark mode is the default; theme toggle persists in `localStorage`.
- Use the shared classes in `resources/css/app.css` (`.card`, `.btn-primary`, `.input`, `.progress-track`, …).
- Keep animations subtle and respect `prefers-reduced-motion`.
- Every change must keep `php artisan test` green.
