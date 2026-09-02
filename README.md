# TutorPay

A lightweight **tutor class fee management system**. TutorPay helps a private tutor keep
track of monthly student fees, record payments, and see who still owes money.

It is deliberately **not** a school management system — there is no attendance, no grading,
no timetabling. Just students, monthly fees, payments and balances.

## Features

- **Students** — add, edit, search and deactivate students, each with their own monthly fee.
- **Monthly fees** — generate the month's fee records for every active student in one click,
  or add a one-off fee for a single student. Generation is idempotent: a student is billed
  at most once per month.
- **Payments** — record full or partial payments against a specific billing month. Payments
  can never exceed the amount still owed for that month.
- **Outstanding balances** — per fee, per student and across all months.
- **Monthly collection** — billed vs collected vs outstanding for the last six months, plus a
  breakdown of the students who still owe money.
- **Payment history** — filter by student and date range with a running total.

Every tutor only ever sees their own students, fees and payments (enforced by policies).

## Tech stack

| Layer     | Choice                                   |
|-----------|------------------------------------------|
| Framework | Laravel 13 (PHP 8.3+)                    |
| Database  | MySQL (SQLite is used for the test suite)|
| Views     | Blade + Blade components                 |
| CSS       | Tailwind CSS 4 (`@tailwindcss/vite`)     |
| JS        | Alpine.js, bundled with Vite             |
| Auth      | Laravel Breeze (Blade stack)             |

No Vue, React, Livewire, Inertia or API layer.

## Getting started

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

# point .env at your MySQL database, then:
php artisan migrate

# optional demo data (one tutor, 8 students, 3 months of fees and payments)
php artisan db:seed

npm run build      # or: npm run dev
php artisan serve
```

The seeder creates a demo tutor: `tutor@tutorpay.test` / `password`.

### Configuration

`config/tutorpay.php` holds the two app-specific settings, both overridable via `.env`:

```dotenv
TUTORPAY_CURRENCY_SYMBOL="$"
TUTORPAY_DEFAULT_DUE_DAY=10
```

## Domain model

```
User (tutor)
 └── Student            name, guardian, batch, monthly_fee, status, enrolled_on
      └── Fee           one row per student per billing month (period_month, amount, due_date)
           └── Payment  amount, paid_on, method, reference  (also linked to student + tutor)
```

- **Fee** = what was billed for a month. **Payment** = money actually received for that month.
- Outstanding for a fee = `amount - sum(payments)`; for a student = the sum of that across months.
- A student with billing history cannot be deleted — mark them *inactive* instead.

## Architecture

Plain Laravel MVC, no extra layers:

```
app/
├── Enums/           StudentStatus, PaymentMethod
├── Http/
│   ├── Controllers/ Dashboard, Student, Fee, GenerateFees, Payment, Report (+ Breeze auth)
│   └── Requests/    validation for students, fees and payments
├── Models/          Student, Fee, Payment, User
├── Policies/        StudentPolicy, FeePolicy, PaymentPolicy (ownership checks)
└── Services/
    ├── MonthlyFeeGenerator  idempotent monthly billing
    └── CollectionReport     dashboard/report aggregation
```

Services exist only where logic spans several models (billing generation and reporting);
everything else lives in models, controllers and form requests.

## Routes

| Method | URI                | Name              |
|--------|--------------------|-------------------|
| GET    | `/dashboard`       | `dashboard`       |
| —      | `/students...`     | `students.*` (resource) |
| GET    | `/fees`            | `fees.index`      |
| POST   | `/fees`            | `fees.store`      |
| POST   | `/fees/generate`   | `fees.generate`   |
| DELETE | `/fees/{fee}`      | `fees.destroy`    |
| GET    | `/payments`        | `payments.index`  |
| GET    | `/payments/create` | `payments.create` |
| POST   | `/payments`        | `payments.store`  |
| DELETE | `/payments/{payment}` | `payments.destroy` |
| GET    | `/reports/monthly` | `reports.monthly` |

All of the above require `auth` (and `verified`). Breeze supplies login, registration,
password reset/confirmation and the profile screens.

## Tests

```bash
php artisan test
```

Feature coverage: student CRUD and ownership rules, monthly fee generation (including
idempotency and enrolment cut-off), single fee billing, payment recording/validation/deletion,
payment history filters, dashboard and report totals. Unit coverage: student balance maths.
