<?php

use App\Models\Fee;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Fee::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Student::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->comment('Tutor who recorded the payment')->constrained();
            $table->decimal('amount', 10, 2);
            $table->date('paid_on');
            $table->string('method', 30)->default('cash');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'paid_on']);
            $table->index('paid_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
