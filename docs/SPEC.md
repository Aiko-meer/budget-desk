# Personal Budget & Savings System — Laravel Prompt Plan

A build plan you can feed to Qoder (or any coding agent) one phase at a time. It is written for a **private, single-user** app: no teams, no registration, no bank APIs, no cloud.

## 1. What you are building

A Laravel web app that answers four questions every day:

1. **How much have I spent today and this month?**
2. **What is my current payday, and how much of it is left?**
3. **How much should I move to savings from this 15-day salary?**
4. **How much is in each bank account and each savings goal?**

Core modules: dashboard, expenses, paydays (15th & end of month), monthly budgets, recurring bills, savings goals and contributions, bank accounts and transfers, reports, settings.

---

## 2. Decisions locked in

| Item | Choice |
|---|---|
| Stack | Latest stable Laravel, Blade + Livewire + Tailwind CSS, Vite |
| Database | Local MySQL 8 (database name: `budget`) |
| Auth | Single seeded user, registration disabled |
| Pay cycle | Semi-monthly: 15th and last day of the month |
| Savings advice | Percent of each payday (default 20%), rounded to ₱100, shown with bills-aware "safe to spend" |
| Currency | PHP (₱) by default, stored in settings so it can be changed |
| Timezone | `Asia/Manila` |
| Charts | Chart.js bundled through Vite |
| Tests | Pest feature + unit tests |
| UI language | English |

**Weekly rhythm of the app:** payday → log income → read advice → transfer savings → log daily expenses → review the month.

---

## 3. How to use this plan (token-efficient workflow)

The goal is to never paste the whole spec into every chat.

1. Create the Laravel project once (section 6.1).
2. Copy **this file** into the repo as `docs/SPEC.md`. It becomes the source of truth, including the data model (section 5) and the acceptance numbers (section 9).
3. Create `AGENTS.md` at the repo root using the rules in section 6.2. Qoder reads it automatically; other agents can be told to read it.
4. Do **one phase per chat**. Start the chat with one sentence: *"Read AGENTS.md and docs/SPEC.md first. Then implement the phase below. Do not restate or summarize the spec."* Paste only that phase's prompt.
5. Do not start the next phase until the phase's acceptance checks pass and `php artisan test` is green.
6. Commit after every phase: `git add . && git commit -m "phase N: <name>"`.
7. If the agent drifts or invents features, revert that phase and re-run with a narrower prompt.
8. If the agent loses context in a long chat, start a fresh chat with the same one-line instruction and a short progress note ("Phases 1–4 are done; Phase 5 next").

Why this works: the agent re-reads the same local files instead of you re-pasting context, and each phase has explicit "do not build" boundaries.

---

## 4. Money rules (binding)

These rules must be implemented exactly. They are the part of the app most likely to go wrong.

### 4.1 Pay periods vs calendar months

Two different views, two different purposes:

- **Pay period** starts on a payday and ends the day before the next payday.
  - Aug 15 payday → period Aug 15–30 (16 days).
  - Aug 31 payday → period Aug 31–Sep 14 (15 days).
  - Feb 15 payday → period Feb 15–27; Feb 28 payday → Feb 28–Mar 14.
- **Calendar month** is used for budgets, monthly reports, and "monthly savings".
- An expense belongs to the pay period that contains its date. An expense dated exactly on a payday belongs to the **new** period.
- Paydays for the current and next month are generated automatically on 15th and last day; the user can add or edit a payday manually.

### 4.2 Savings advice for one payday

For each payday, compute in this order:

1. `raw_target = net_salary × savings_rate / 100`
2. `recommended_savings = round(raw_target / rounding_step) × rounding_step`
   - default step ₱100; step 0 = no rounding.
3. `bills_due` = sum of active bills whose due date falls **within that pay period** (payday through period end, inclusive). A bill due on a payday is paid from that payday.
4. `safe_to_spend = max(0, net_salary − bills_due − recommended_savings)`
5. `daily_safe_spend = safe_to_spend / days_in_period`, rounded to 2 decimals.
6. `actual_saved` = sum of transfers dated in that period **into** accounts flagged `counts_as_savings`, minus transfers **out of** savings accounts tagged to a goal in that period. Transfers between two savings accounts never count as new savings.
7. Status: `met` if within ₱1 of target, `short` below, `ahead` above.

**Shortfall rule:** if `net_salary − bills_due < recommended_savings`, warn:
"Bills alone leave ₱X. Saving ₱Y would overdraw this payday. Save at most ₱X this period, or reduce a bill."

**Advice copy style** (plain, specific, no jargon):

> Payday Aug 15: set aside **₱4,000** (20% of ₱20,000). Bills before Aug 31: **₱3,500**. That leaves **₱12,500** for 16 days — about **₱781/day**.

### 4.3 What "saved" means

- A **saving** is money moved from a non-savings account into an account with `counts_as_savings = true`, optionally tagged to a goal.
- **Goal progress** = contributions tagged to that goal minus withdrawals tagged to that goal.
- The **emergency fund** is a goal with `is_emergency_fund = true`.
  - If it has no stored target, suggested target = 3 × average monthly expenses of the last 3 complete calendar months. Fallback: 3 × current month's total budget. Fallback: user-entered target.
- Transfers are **not** expenses and **not** income.
- An expense paid directly from a savings account reduces the account balance but does not change goal progress (log a goal withdrawal instead if the money came out of a goal).

### 4.4 Account balance (always computed, never stored)

```
balance = opening_balance
        + paydays deposited into this account
        + transfers in
        - transfers out
        - expenses paid from this account
```

- No `balance` column exists in the database.
- Allow a transfer that exceeds the source balance only with a warning, not a hard block (real life has pending deposits).
- Expenses have no account are allowed only if the account is deleted? No — require an account for every expense. Keep it required.

### 4.5 Edge cases the code must handle

- Bill due day 31 in February → last day of that month. Paid status is per due month.
- Editing a payday date recalculates adjacent periods; it must not delete expenses or transfers.
- Deleting a payday does not delete records; those dates simply fall into the previous period.
- Two paydays cannot share the same date.
- `safe_to_spend` never goes negative in the UI; show a shortfall warning instead.
- Rounding is half-up, done only for the savings target, never for stored amounts.
- All money is stored as `decimal(12,2)` and displayed with the configured symbol and thousands separators.
- Dates are date-only; use the app timezone `Asia/Manila`; never use timestamps for business dates.

---

## 5. Data model

Use these tables and columns. Timestamps and Laravel defaults are implied.

### settings (singleton row, id = 1)

| Column | Type | Notes |
|---|---|---|
| currency_code | char(3) | default `PHP` |
| currency_symbol | varchar(8) | default `₱` |
| savings_rate | decimal(5,2) | default `20.00`, range 0–100 |
| savings_rounding | unsigned int | default `100` (peso step), 0 = exact |
| emergency_fund_months | tinyint | default `3` |
| timezone | varchar(64) | default `Asia/Manila` |

### accounts

| Column | Type | Notes |
|---|---|---|
| name | varchar(80) | e.g. "BPI Checking" |
| institution | varchar(80) nullable | bank / e-wallet name |
| type | enum: cash, checking, savings, e_wallet, investment | |
| counts_as_savings | boolean | default false; true for savings/investment |
| opening_balance | decimal(12,2) | default 0 |
| color | varchar(20) nullable | UI accent |
| is_active | boolean | default true |
| sort_order | unsigned int | default 0 |
| notes | text nullable | |

### paydays

| Column | Type | Notes |
|---|---|---|
| pay_date | date, unique | 15th or last day, or manual |
| gross_amount | decimal(12,2) | optional info |
| net_amount | decimal(12,2) | amount actually received |
| account_id | FK accounts, restrict delete | where it was deposited |
| notes | text nullable | |

### categories

| Column | Type | Notes |
|---|---|---|
| name | varchar(60), unique | Food, Transport, Rent… |
| group | enum: needs, wants, bills, other | used for grouping/reports |
| color | varchar(20) nullable | |
| default_monthly_budget | decimal(12,2) nullable | prefill for new months |
| is_active | boolean | default true |
| sort_order | unsigned int | default 0 |

### budgets

| Column | Type | Notes |
|---|---|---|
| category_id | FK categories, cascade | |
| month | char(7) | `YYYY-MM` |
| amount | decimal(12,2) | |

Unique: (`category_id`, `month`).

### expenses

| Column | Type | Notes |
|---|---|---|
| spent_on | date, index | |
| amount | decimal(12,2) | |
| category_id | FK categories, restrict | |
| account_id | FK accounts, restrict | required |
| bill_id | FK bills, nullable, set null on delete | if this is a bill payment |
| note | varchar(255) nullable | |

Pay period is derived from `spent_on`, not stored.

### bills (recurring templates, no auto-created expenses)

| Column | Type | Notes |
|---|---|---|
| name | varchar(80) | Rent, Internet… |
| amount | decimal(12,2) | expected amount |
| category_id | FK categories, restrict | |
| account_id | FK accounts, nullable | default paying account |
| due_day | tinyint | 1–31; 31 = last day of month |
| is_active | boolean | default true |
| notes | text nullable | |

"Paid this month" is derived: an expense with `bill_id` in the bill's due month.

### transfers (the only way money moves between accounts)

| Column | Type | Notes |
|---|---|---|
| transferred_on | date | |
| from_account_id | FK accounts, restrict | |
| to_account_id | FK accounts, restrict | |
| amount | decimal(12,2) | > 0 |
| goal_id | FK savings_goals, nullable, set null on delete | tags a contribution or withdrawal |
| note | varchar(255) nullable | |

App-level validation: `from_account_id != to_account_id`.

### savings_goals

| Column | Type | Notes |
|---|---|---|
| name | varchar(80) | Emergency Fund, Travel… |
| target_amount | decimal(12,2) nullable | null = open-ended or computed |
| target_date | date nullable | optional deadline |
| is_emergency_fund | boolean | default false; only one allowed |
| color | varchar(20) nullable | |
| is_active | boolean | default true |
| notes | text nullable | |

### users

Default Laravel table. Seed exactly one user. No registration routes.

### Derived services (no tables)

- `PayPeriodService` — periods, current period, next payday, days in period, period for a date.
- `SavingsAdvisor` — target, bills due, safe to spend, daily amount, actual saved, status, advice copy.
- `BalanceService` (or account accessors) — balances and ledgers.
- `Money` helper — formatting with settings symbol.

---

## 6. Build phases and prompts

### 6.1 Manual setup (no AI needed)

```bash
# Requirements: PHP 8.2+, Composer, MySQL 8, Node 20+
laravel new budget-app
# choose the Livewire starter kit, database MySQL, database name "budget"
cd budget-app
php artisan migrate
npm install
npm run dev
git init && git add . && git commit -m "initial scaffold"
```

Create the MySQL database first if the installer cannot:

```sql
CREATE DATABASE budget CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

In `.env`: set `DB_*`, `APP_TIMEZONE=Asia/Manila`, and a local `SEED_PASSWORD`.

Then copy this document into `docs/SPEC.md`.

### 6.2 Create `AGENTS.md` (paste this once)

```markdown
# Project rules — Personal Budget App

1. This is a private, single-user app. Never add registration, teams, multi-tenancy, public APIs, or roles.
2. `docs/SPEC.md` is binding, especially sections 4 (money rules), 5 (schema), and 9 (acceptance numbers). Ask before changing the schema or a rule; when approved, update SPEC.md in the same commit.
3. Money is `decimal(12,2)`, cast on models, never stored as float. Rounding happens only for the savings target.
4. Balances, budget actuals, savings totals, and goal progress are computed on read. Never store balances.
5. Transfers are the only way to move money between accounts. Savings = transfers from non-savings accounts into accounts with `counts_as_savings = true`.
6. A pay period starts on a payday and ends the day before the next payday. Calendar months are only for budgets and monthly reports.
7. Every change ships with migrations and Pest tests; `php artisan test` must pass before you report done.
8. UI: Blade + Livewire + Tailwind, responsive, empty states, peso formatting from settings, plain-language advice.
9. Do not add packages beyond Chart.js and what Laravel ships with unless you explain why first.
10. No destructive data operations (migrate:fresh, truncate, deletes) without explicit confirmation.
```

### 6.3 Prompt 1 — Foundation, auth, layout, settings

```text
Read AGENTS.md and docs/SPEC.md (sections 2, 4, 5). Implement Phase 1 only.

Goal: a working Laravel shell with single-user login, app layout, and settings.

Do:
1. Configure MySQL (database "budget"), timezone Asia/Manila, and local seed password.
2. Keep the installed Livewire authentication. Disable registration routes completely (no route, 404). Seed exactly one local user from env (SEED_EMAIL default me@local.test, SEED_PASSWORD default "password" only when APP_ENV=local).
3. Create the settings table and Settings model for the singleton row (id=1). Add a Settings page with a Livewire form for currency code, currency symbol, savings rate (0–100), rounding step (0–1000), and emergency fund months. Validate and flash success.
4. Build the app layout: left sidebar (Dashboard, Expenses, Paydays, Bills, Budgets, Savings, Accounts, Reports, Settings), top bar with the app name and user menu, mobile drawer, Tailwind styling. Use a consistent page-header component.
5. Add a Money helper that formats amounts using the stored symbol, thousands separators, and 2 decimals.
6. Add a placeholder Dashboard page so login lands somewhere real.
7. Tests: guest is redirected to login; registration URL 404s; the seeded user can log in; settings save and reload correctly.

Do not build accounts, expenses, bills, savings, or charts yet. Run migrations and php artisan test. List changed files and paste the test result.
```

**Acceptance:** login works, registration is gone, settings persist, sidebar navigation renders on desktop and mobile, tests pass.

### 6.4 Prompt 2 — Accounts and transfers

```text
Read AGENTS.md and docs/SPEC.md (sections 4.4, 5). Implement Phase 2 only: accounts and transfers.

Do:
1. Create the accounts table exactly per SPEC, plus model, factory, and a small seeder (BPI Checking: checking, opening 5000; BPI Savings: savings, counts_as_savings true, opening 0; Cash: cash, opening 0).
2. Accounts page: list cards with computed balance and total cash across accounts. Create/edit/archive accounts. Do not store balances.
3. Account detail page: header with computed balance and a running ledger (payday deposits, expenses, transfers in/out) ordered by date with a running balance. Show empty state when no entries.
4. Transfers page: date, from account, to account, amount, optional goal (disabled until the savings phase — hide the field for now), note. Validate different accounts, amount > 0, date required. If amount exceeds the source balance, show a non-blocking warning.
5. Implement BalanceService that computes balances with a single query per account if practical.
6. Tests: balance math with opening balance, payday deposit, expense, transfer in/out; transferring to the same account fails validation; a savings transfer counts as savings only when the source account is not a savings account.

Do not build paydays, expenses, bills, budgets, or savings goals yet. Run php artisan test and report.
```

**Acceptance:** total balances correct, ledger ordering correct, transfer validation works, tests pass.

### 6.5 Prompt 3 — Paydays and pay periods

```text
Read AGENTS.md and docs/SPEC.md (sections 4.1, 4.5, 5). Implement Phase 3 only: paydays and the pay-period engine.

Do:
1. Create the paydays table exactly per SPEC.
2. Create PayPeriodService with tested methods: paydaysAround(date), currentPeriod(), nextPayday(after), periodForPayday(payday), periodForDate(date), daysInPeriod(period), ensureSemiMonthlyPaydays(year, month).
3. Paydays page: list grouped by month with net amount and deposited account; create/edit/delete. Validate unique pay_date and net_amount >= 0.
4. A "Generate schedule" action that creates the 15th and last-day paydays for a chosen year, skipping existing dates. Do not create duplicate paydays.
5. Show the current pay period on the Dashboard placeholder: period dates, next payday countdown, and net salary received in the period.
6. When a payday is saved, its deposit automatically reflects in the account balance (computed, no extra table).
7. Tests: Aug 2026 gives periods Aug 15–30 and Aug 31–Sep 14; Feb 2026 gives Feb 15–27 and Feb 28–Mar 14; leap year Feb 2028 gives Feb 15–28 and Feb 29–Mar 14; Dec 31–Jan 14 works; an expense date can be mapped to the correct period; duplicate payday rejected.

Do not build expenses, bills, budgets, savings, or the advisor yet. Run tests and report.
```

**Acceptance:** all boundary tests pass; current-period card shows correct dates and countdown.

### 6.6 Prompt 4 — Categories and monthly budgets

```text
Read AGENTS.md and docs/SPEC.md (section 5). Implement Phase 4 only: categories and budgets.

Do:
1. Create categories and budgets tables exactly per SPEC, with models, factories, and a useful default category seeder: Food (needs), Transport (needs), Rent (bills), Utilities (bills), Internet (bills), Health (needs), Entertainment (wants), Savings buffer (other).
2. Categories page: create/edit/archive, choose group, color, default monthly budget.
3. Budgets page: month switcher (default current month, previous/next), one row per active category with budget amount editable inline, actual spent, remaining, and a progress bar (red when over budget). Show totals row.
4. "Copy previous month" and "Apply default budgets" actions with confirmation.
5. Budget actuals are calendar-month sums of expenses per category. Never store actuals.
6. Tests: budget totals per category and month, copy-previous behavior, months are isolated (an August budget does not appear in September), over-budget flag.

Do not build expenses UI, bills, savings, or reports yet. Run tests and report.
```

**Acceptance:** budgets editable per month, actuals correct once expenses exist, copy actions work.

### 6.7 Prompt 5 — Expenses and quick add

```text
Read AGENTS.md and docs/SPEC.md (sections 4.1, 5). Implement Phase 5 only: expenses.

Do:
1. Create the expenses table exactly per SPEC, with model, factory, and policy-free Livewire components.
2. Expenses page: filter by month (default current), category, account, and free-text note; show total for the filtered set. Table columns: date, note, category, account, amount, pay period, actions. Create/edit/delete with confirmation.
3. Quick-add: a persistent "Add expense" button on every page opening a modal with amount, category, account, date (default today), note. Submit and keep the modal open for fast entry? No — close on success, show a flash with the new expense.
4. Validation: amount > 0, category and account required, date within 5 years of today. Do not allow future dates beyond today.
5. Display the derived pay period for each expense (e.g. "Aug 15–30") without storing it.
6. Expense totals must respect filters.
7. Tests: create/edit/delete; required fields; future date rejected; filters and totals; an Aug 15 expense maps to the Aug 15–30 period, Aug 30 stays in that period, Aug 31 starts the new period.

Do not build bills, savings, reports, or charts yet. Run tests and report.
```

**Acceptance:** fast entry works, filters correct, period labels correct, tests pass.

### 6.8 Prompt 6 — Bills and upcoming payments

```text
Read AGENTS.md and docs/SPEC.md (sections 4.5, 5). Implement Phase 6 only: recurring bills.

Do:
1. Create the bills table exactly per SPEC, with model, factory, and seeder examples: Rent ₱8,000 due 5 (Bills), Internet ₱1,500 due 20 (Bills), Electricity ₱2,000 due 25 (Bills).
2. Bills page: create/edit/deactivate; show next due date (handle day 31 in short months), expected amount, category, default account.
3. Upcoming bills widget: next 45 days grouped by pay period, with the due date and total per period. Mark a bill as "paid this month" when an expense linked to that bill exists in its due month.
4. "Log payment" action pre-fills amount, category, account, and date = due date; it creates an expense with bill_id. Do not auto-create expenses on a schedule.
5. Bills contribute to the pay-period "bills due" number used by the advisor. Add a query method for this now, even if the advisor UI comes later.
6. Tests: due date for day 31 in February; paid status per month; upcoming totals per period; logging payment creates an expense and updates account balance.

Do not build savings, advisor UI, reports, or charts yet. Run tests and report.
```

**Acceptance:** upcoming list matches the pay periods; bill payments reduce balances; tests pass.

### 6.9 Prompt 7 — Savings goals, contributions, and the advisor

```text
Read AGENTS.md and docs/SPEC.md (sections 4.2, 4.3, 5). Implement Phase 7 only: savings and the savings advisor.

Do:
1. Create savings_goals per SPEC, with a seeder for an Emergency Fund goal (is_emergency_fund true, target 54000 for the demo, target date null).
2. Goal CRUD: create/edit/archive; only one emergency fund may be active (validate).
3. Savings page sections:
   a. Headline cards: total savings (sum of balances of accounts with counts_as_savings), saved this month, saved this payday, emergency fund progress.
   b. Goals list with progress bars (contributions minus withdrawals tagged to the goal), remaining amount, and target date.
   c. Contributions list: transfers into savings accounts, with goal, source account, date, amount, note.
   d. Per-payday table: payday date, net salary, target, actual saved, difference, status (met/short/ahead), for the current and previous 2 months.
4. Add a "Save now" flow: choose source account, destination savings account, amount (pre-filled with the current recommended target), optional goal, date. It creates a transfer. Reject same-account and non-savings destinations.
5. Implement SavingsAdvisor exactly per SPEC §4.2: raw target, rounded target, bills due in the period, safe to spend, daily safe spend, actual saved, status, shortfall advice, and plain-language copy. Unit-test it.
6. Emergency fund suggested target: 3 × average monthly expenses of the last 3 complete months; fall back to 3 × current month budget; otherwise use the stored target. Show which source was used.
7. Tests: rounding (20,050 at 20% → 4,000); shortfall case (net 10,000, bills 9,000, target 2,000 → max save 1,000, safe 0); savings transfer from savings to savings does not count; goal withdrawal reduces progress; per-payday statuses.

Do not build reports, charts, or a dashboard redesign yet. Run tests and report.
```

**Acceptance:** per-payday numbers match SPEC §9; savings totals and goal progress are correct; tests pass.

### 6.10 Prompt 8 — Dashboard

```text
Read AGENTS.md and docs/SPEC.md (sections 4.2, 9). Implement Phase 8 only: the dashboard.

Do:
1. Rebuild the Dashboard page with these widgets, in this order:
   a. Current pay period card: period dates, payday date, net salary, bills due, recommended savings, actual saved, status.
   b. Savings advice card using the advisor copy style in SPEC §4.2, with a "Save now" button.
   c. Safe-to-spend strip: remaining amount, days left, daily amount. If shortfall, show a warning instead.
   d. Upcoming bills for this and the next period.
   e. Account balance cards with total cash.
   f. This month's spending vs budget bar and top 3 categories.
   g. Recent 8 transactions (expenses, transfers, paydays) with icons/types.
2. Keep the quick-add expense button global.
3. Mobile layout: single column, cards stack; desktop: two-column grid.
4. Empty states for a fresh database, each linking to the action that fills it.
5. Tests: dashboard renders for a user with no data; snapshot-free assertions that the numbers from SPEC §9 appear after seeding the scenario.

Do not add charts or reports pages yet. Run tests and report.
```

**Acceptance:** the whole screen is readable at a glance; numbers match the acceptance scenario; mobile layout works.

### 6.11 Prompt 9 — Reports and charts

```text
Read AGENTS.md and docs/SPEC.md (section 9). Implement Phase 9 only: reports and charts.

Do:
1. Reports page with a month selector (current year, plus previous 12 months):
   a. Summary: income, expenses, saved (transfers into savings), net cash flow.
   b. Expenses by category, with amount and % of total, sorted descending.
   c. Budget vs actual per category for the month.
   d. Month-by-month table for the last 6 months.
2. Charts with Chart.js bundled via Vite (no CDN):
   a. Last 6 months: income vs expenses vs savings (grouped bars).
   b. Current month category breakdown (doughnut).
   c. Savings cumulative for the last 6 months (line).
3. CSV export of filtered transactions (date, type, category, account, amount, note, pay period).
4. Print-friendly report styles (basic @media print).
5. Tests: aggregate totals for a seeded month; savings excluded from expenses; net cash flow formula; CSV endpoint returns expected rows.

Do not add new data models or change existing rules. Run tests and report.
```

**Acceptance:** reports reconcile with the dashboard and SPEC numbers; charts render without console errors; CSV opens correctly.

### 6.12 Prompt 10 — Demo data, backup, and README

```text
Read AGENTS.md and docs/SPEC.md (section 9). Implement Phase 10 only: demo seed, backup, and docs.

Do:
1. Create a DemoSeeder that inserts exactly the scenario in SPEC §9: settings, accounts, paydays, bills, categories, budgets, expenses, transfers, and the Emergency Fund goal. Make it runnable with `php artisan db:seed --class=DemoSeeder` and clearly separate from production data.
2. Add `php artisan budget:backup` that writes a timestamped JSON file to storage/app/backups with all tables, and a `budget:backup:prune --keep=14` that deletes older backups. No automatic restore.
3. Settings page: buttons "Export data (JSON)" and "Export transactions (CSV)".
4. README: setup commands, MySQL config, seed user, how to run, how to back up, how the savings advice is calculated (short version), and a warning that this is a private local app.
5. Tests: DemoSeeder produces the SPEC §9 expected totals; backup command writes a readable JSON file with expected keys.

Do not add import/restore, cloud sync, or scheduling. Run tests and report.
```

**Acceptance:** fresh setup + demo seed reproduces the acceptance numbers exactly; backups open as valid JSON.

### 6.13 Prompt 11 — Polish and final QA

```text
Read AGENTS.md and docs/SPEC.md (section 8 and 9). Implement Phase 11 only: polish and QA.

Do:
1. UX pass: consistent spacing, empty states, disabled states, loading indicators on Livewire actions, friendly validation messages, keyboard-friendly forms, focus styles.
2. Remove every unused starter-kit page, route, and view. Confirm no registration route exists.
3. Mobile pass at 375px: sidebar drawer, tables become cards or horizontal scroll, quick-add reachable.
4. Formatting audit: all money through the Money helper; dates as "Aug 15, 2026"; pay periods as "Aug 15–30".
5. Security: APP_DEBUG false guidance for non-local use, no secrets in the repo, no public exposure instructions. This app is single-user and local.
6. Run the full SPEC §9 acceptance scenario manually in the browser and fix any mismatch.
7. Run php artisan test and report the full result. List any known limitations in README.

Do not add new features. If something in the spec is ambiguous, choose the simplest interpretation and note it in docs/SPEC.md under "Decisions".
```

**Acceptance:** the app passes the full manual checklist, tests are green, and the README is accurate.

---

## 7. Reusable prompts

**Bug fix**

```text
Read AGENTS.md and docs/SPEC.md. Bug: <one sentence>.
Steps: <1,2,3>. Expected: <...>. Actual: <...>.
Reproduce it with a failing Pest test first, then fix it without changing unrelated behavior. Run php artisan test and report the root cause.
```

**Change request**

```text
Read AGENTS.md and docs/SPEC.md. Change: <what>. Reason: <why>.
Constraints: keep the schema and money rules unless this change requires one; if it does, propose the migration first and wait for approval. Add tests. Run php artisan test.
```

**Verify the math**

```text
Read AGENTS.md and docs/SPEC.md. Without changing code, calculate by hand for payday <date>:
net salary, raw target, rounded target, bills due in the period, safe to spend, daily safe spend, actual saved, status.
Then run the app's SavingsAdvisor for the same inputs and show both results side by side. Explain any difference.
```

**Fresh-chat re-orientation**

```text
Read AGENTS.md and docs/SPEC.md. Phases 1–<N> are complete and tests pass. Next is Phase <N+1>. Do not re-implement earlier phases. Begin by summarizing the current schema and the files relevant to this phase in 5 bullets, then implement it.
```

---

## 8. Acceptance test (also paste into `docs/SPEC.md`)

Use this scenario to verify the build. Year: 2026.

**Settings:** currency PHP/₱, savings rate 20%, rounding ₱100, emergency months 3.

**Accounts:** BPI Checking (checking, opening ₱5,000), BPI Savings (savings, `counts_as_savings`, opening ₱0).

**Paydays:**
| Date | Gross | Net | Account |
|---|---|---|---|
| Aug 15, 2026 | ₱20,000 | ₱20,000 | BPI Checking |
| Aug 31, 2026 | ₱20,000 | ₱20,000 | BPI Checking |

**Bills:** Rent ₱8,000 due 5; Internet ₱1,500 due 20; Electricity ₱2,000 due 25.

**Pay periods:**
| Period | Days | Bills due | Target | Safe to spend | Daily |
|---|---|---|---|---|---|
| Aug 15–30 | 16 | ₱3,500 | ₱4,000 | ₱12,500 | ₱781.25 |
| Aug 31–Sep 14 | 15 | ₱8,000 | ₱4,000 | ₱8,000 | ₱533.33 |

**Transactions:**
- Aug 16: Groceries ₱1,200 (Food, checking)
- Aug 18: Ride ₱350 (Transport, checking)
- Aug 20: Internet bill payment ₱1,500 (linked bill)
- Aug 25: Electricity bill payment ₱2,300 (linked bill, over expected)
- Aug 28: Dinner ₱900 (Entertainment, checking)
- Aug 15: transfer checking → savings ₱4,000 (goal: Emergency Fund)
- Aug 31: transfer checking → savings ₱3,500 (goal: Emergency Fund)
- Sep 5: Rent ₱8,000 (linked bill)

**Expected results:**
- Aug 15 advice: save ₱4,000, bills ₱3,500, safe ₱12,500, daily ₱781.25, status met.
- Aug 31 advice: save ₱4,000, bills ₱8,000, safe ₱8,000, daily ₱533.33, actual ₱3,500, status short by ₱500.
- Savings page, August: ₱7,500 saved; cumulative goal ₱7,500 of ₱54,000 = 13.9%.
- Balances at Aug 31: BPI Checking ₱31,250; BPI Savings ₱7,500; total ₱38,750.
- After Sep 5 rent: BPI Checking ₱23,250; total cash ₱30,750.
- August report: income ₱40,000; expenses ₱6,250; saved ₱7,500; net cash flow +₱26,250.
- Budget actuals for August (if budgets: Rent ₱8,000, Food ₱5,000, Transport ₱2,000, Bills ₱11,500): Food ₱2,100; Transport ₱350; Bills ₱3,800; Rent ₱0 (paid in September).

**Unit edge cases:**
- Net ₱20,050 at 20% → raw ₱4,010 → target ₱4,000.
- Net ₱10,000, bills ₱9,000, target ₱2,000 → max safe saving ₱1,000, safe to spend ₱0, warning shown.
- Feb 2026: paydays Feb 15 and Feb 28; Feb 28 period ends Mar 14.
- Bill due day 31 in Feb 2026 → Feb 28.
- Transfer savings → savings does not increase "saved this payday".
- Expense on Aug 15 belongs to Aug 15–30; Aug 14 belongs to the previous period.

---

## 9. Daily use after the build

1. **Payday (15th / end of month):** log the net salary, open the advice card, transfer the recommended amount to savings, tag a goal.
2. **Daily:** tap Add expense; amount, category, account. Keep the note short.
3. **When a bill is paid:** use "Log payment" on the bill so it counts as paid and is linked.
4. **Weekly:** check the dashboard; if safe-to-spend daily is falling, slow down.
5. **Month end:** review Reports, adjust next month's budgets, run `php artisan budget:backup`.
6. **Keep it private:** local machine only. Do not expose this to the internet without adding proper hardening.

Optional later (do not build now): CSV bank imports, recurring expense automation, email/Telegram reminders, PWA install, multi-currency.
