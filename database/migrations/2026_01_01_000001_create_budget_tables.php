<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->char('currency_code', 3)->default('PHP');
            $table->string('currency_symbol', 8)->default('₱');
            $table->decimal('savings_rate', 5, 2)->default(20);
            $table->unsignedInteger('savings_rounding')->default(100);
            $table->unsignedTinyInteger('emergency_fund_months')->default(3);
            $table->string('timezone', 64)->default('Asia/Manila');
            $table->timestamps();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('institution', 80)->nullable();
            $table->enum('type', ['cash', 'checking', 'savings', 'e_wallet', 'investment'])->default('checking');
            $table->boolean('counts_as_savings')->default(false);
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->string('color', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->enum('group', ['needs', 'wants', 'bills', 'other'])->default('other');
            $table->string('color', 20)->nullable();
            $table->decimal('default_monthly_budget', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('savings_goals', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->decimal('target_amount', 12, 2)->nullable();
            $table->date('target_date')->nullable();
            $table->boolean('is_emergency_fund')->default(false);
            $table->string('color', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->decimal('amount', 12, 2);
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('due_day')->default(1);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->char('month', 7);
            $table->decimal('amount', 12, 2);
            $table->timestamps();
            $table->unique(['category_id', 'month']);
            $table->index('month');
        });

        Schema::create('paydays', function (Blueprint $table) {
            $table->id();
            $table->date('pay_date')->unique();
            $table->decimal('gross_amount', 12, 2)->nullable();
            $table->decimal('net_amount', 12, 2);
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('spent_on');
            $table->decimal('amount', 12, 2);
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->foreignId('bill_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $table->index('spent_on');
        });

        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->date('transferred_on');
            $table->foreignId('from_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('to_account_id')->constrained('accounts')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->foreignId('goal_id')->nullable()->constrained('savings_goals')->nullOnDelete();
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $table->index('transferred_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('paydays');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('bills');
        Schema::dropIfExists('savings_goals');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('settings');
    }
};
