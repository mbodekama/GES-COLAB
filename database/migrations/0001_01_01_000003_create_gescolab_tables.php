<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // EMPLOYEES
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('matricule')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('nationality')->default('Ivoirienne');
            $table->enum('marital_status', ['single','married','divorced','widowed'])->default('single');
            $table->unsignedTinyInteger('children_count')->default(0);
            $table->text('address')->nullable();
            $table->string('cnps_number')->nullable();
            $table->string('position');
            $table->string('department');
            $table->date('hire_date');
            $table->integer('leave_balance')->default(30);
            $table->enum('status', ['active','on_leave','suspended','terminated'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        // SALARY GRIDS
        Schema::create('salary_grids', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->unsignedTinyInteger('level')->default(1);
            $table->decimal('min_salary', 12, 2)->default(0);
            $table->decimal('max_salary', 12, 2)->default(0);
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->decimal('transport_allowance', 12, 2)->default(0);
            $table->decimal('housing_allowance', 12, 2)->default(0);
            $table->decimal('meal_allowance', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // CONTRACTS
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_grid_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contract_number')->unique();
            $table->enum('type', ['cdi','cdd','internship','consulting']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('trial_end_date')->nullable();
            $table->string('position');
            $table->string('department');
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->enum('status', ['active','expired','terminated','renewed'])->default('active');
            $table->timestamp('signed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // LEAVES
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('leave_number')->unique();
            $table->enum('type', ['annual','sick','permission','exceptional','maternity','paternity']);
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('duration_days');
            $table->text('reason')->nullable();
            $table->string('attachment')->nullable();
            $table->enum('status', ['pending','approved','rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        // PAYROLLS
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('period'); // format: 2026-05
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->decimal('seniority_bonus', 12, 2)->default(0);
            $table->decimal('seniority_rate', 5, 2)->default(0);
            $table->decimal('transport_allowance', 12, 2)->default(0);
            $table->decimal('housing_allowance', 12, 2)->default(0);
            $table->decimal('meal_allowance', 12, 2)->default(0);
            $table->decimal('gross_salary', 12, 2)->default(0);
            $table->decimal('cnps_employee', 12, 2)->default(0);
            $table->decimal('cnps_employer', 12, 2)->default(0);
            $table->decimal('igr', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);
            $table->unsignedTinyInteger('worked_days')->default(26);
            $table->unsignedTinyInteger('leave_days')->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'period']);
        });

        // MESSAGES
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('leaves');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('salary_grids');
        Schema::dropIfExists('employees');
    }
};
