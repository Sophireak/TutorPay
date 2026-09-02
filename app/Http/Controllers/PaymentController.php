<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Fee;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $tutor = $request->user();

        $payments = Payment::query()
            ->ownedBy($tutor)
            ->with(['student', 'fee'])
            ->when(
                $request->filled('student_id'),
                fn ($query) => $query->where('student_id', $request->integer('student_id'))
            )
            ->betweenDates($request->string('from')->value() ?: null, $request->string('to')->value() ?: null)
            ->latest('paid_on')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('payments.index', [
            'payments' => $payments,
            'students' => $tutor->students()->orderBy('name')->get(),
            'filters' => [
                'student_id' => $request->string('student_id')->value(),
                'from' => $request->string('from')->value(),
                'to' => $request->string('to')->value(),
            ],
            'total' => round((float) Payment::query()
                ->ownedBy($tutor)
                ->when(
                    $request->filled('student_id'),
                    fn ($query) => $query->where('student_id', $request->integer('student_id'))
                )
                ->betweenDates($request->string('from')->value() ?: null, $request->string('to')->value() ?: null)
                ->sum('amount'), 2),
        ]);
    }

    public function create(Request $request): View
    {
        $fee = Fee::with(['student', 'payments'])->findOrFail($request->integer('fee'));

        $this->authorize('pay', $fee);

        return view('payments.create', [
            'fee' => $fee,
            'methods' => PaymentMethod::cases(),
        ]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $fee = $request->fee();

        $this->authorize('pay', $fee);

        $payment = new Payment($request->safe()->only([
            'amount', 'paid_on', 'method', 'reference', 'notes',
        ]));

        $payment->student()->associate($fee->student);
        $payment->user()->associate($request->user());

        $fee->payments()->save($payment);

        return redirect()
            ->route('students.show', $fee->student)
            ->with('status', 'Payment recorded.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $this->authorize('delete', $payment);

        $student = $payment->student;
        $payment->delete();

        return redirect()
            ->route('students.show', $student)
            ->with('status', 'Payment deleted.');
    }
}
