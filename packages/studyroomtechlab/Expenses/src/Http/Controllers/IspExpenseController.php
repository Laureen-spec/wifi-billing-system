<?php

namespace StudyRoomTechLab\Expenses\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\IspTenantResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use StudyRoomTechLab\Expenses\Models\IspExpense;

class IspExpenseController extends Controller
{
    public function __construct(private readonly IspTenantResolver $tenantResolver)
    {
    }

    public function index(Request $request): Response
    {
        $this->authorizeTenantAccess($request, ['view-isp-customers', 'manage-isp-customers', 'view-wifi-dashboard', 'manage-wifi-dashboard']);

        $isp = $this->tenantResolver->resolve($request);
        $filters = $request->only(['q', 'category', 'payment_method', 'status', 'from', 'to']);
        $query = $this->baseQuery((int) $isp->id);

        $this->applyFilters($query, $filters);

        $expenses = $query
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate((int) $request->integer('per_page', 15))
            ->withQueryString()
            ->through(fn (IspExpense $expense): array => $this->expensePayload($expense));

        return Inertia::render('expenses/index', [
            'isp' => [
                'id' => $isp->id,
                'name' => $isp->name,
            ],
            'expenses' => $expenses,
            'stats' => $this->stats((int) $isp->id),
            'filters' => $filters,
            'options' => $this->options(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizeTenantAccess($request, ['manage-isp-customers', 'manage-wifi-dashboard']);

        $isp = $this->tenantResolver->resolve($request);

        return Inertia::render('expenses/form', [
            'mode' => 'create',
            'isp' => [
                'id' => $isp->id,
                'name' => $isp->name,
            ],
            'expense' => $this->blankExpense(),
            'options' => $this->options(),
            'storeUrl' => route('expenses.store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeTenantAccess($request, ['manage-isp-customers', 'manage-wifi-dashboard']);

        $isp = $this->tenantResolver->resolve($request);
        $data = $this->validated($request, (int) $isp->id);

        IspExpense::query()->create(array_merge($data, [
            'isp_id' => $isp->id,
            'expense_number' => $this->makeExpenseNumber(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]));

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Expense recorded successfully.');
    }

    public function edit(Request $request, IspExpense $expense): Response
    {
        $this->authorizeTenantAccess($request, ['manage-isp-customers', 'manage-wifi-dashboard']);
        $this->authorizeExpense($request, $expense);

        $isp = $this->tenantResolver->resolve($request);

        return Inertia::render('expenses/form', [
            'mode' => 'edit',
            'isp' => [
                'id' => $isp->id,
                'name' => $isp->name,
            ],
            'expense' => $this->expensePayload($expense),
            'options' => $this->options(),
            'updateUrl' => route('expenses.update', $expense),
        ]);
    }

    public function update(Request $request, IspExpense $expense): RedirectResponse
    {
        $this->authorizeTenantAccess($request, ['manage-isp-customers', 'manage-wifi-dashboard']);
        $this->authorizeExpense($request, $expense);

        $data = $this->validated($request, (int) $expense->isp_id, $expense->id);

        $expense->update(array_merge($data, [
            'updated_by' => $request->user()->id,
        ]));

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Expense updated successfully.');
    }

    public function destroy(Request $request, IspExpense $expense): RedirectResponse
    {
        $this->authorizeTenantAccess($request, ['manage-isp-customers', 'manage-wifi-dashboard']);
        $this->authorizeExpense($request, $expense);

        $expense->delete();

        return back()->with('success', 'Expense removed from the ledger.');
    }

    private function baseQuery(int $ispId)
    {
        abort_unless(Schema::hasTable('isp_expenses'), 500, 'ISP expenses table is missing. Run migrations first.');

        return IspExpense::query()->where('isp_id', $ispId);
    }

    private function applyFilters($query, array $filters): void
    {
        $query->when($filters['q'] ?? null, function ($query, string $search): void {
            $query->where(function ($query) use ($search): void {
                $query->where('expense_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('receipt_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        });

        foreach (['category', 'payment_method', 'status'] as $field) {
            $query->when($filters[$field] ?? null, fn ($query, string $value) => $query->where($field, $value));
        }

        $query->when($filters['from'] ?? null, fn ($query, string $date) => $query->whereDate('expense_date', '>=', $date));
        $query->when($filters['to'] ?? null, fn ($query, string $date) => $query->whereDate('expense_date', '<=', $date));
    }

    private function validated(Request $request, int $ispId, ?int $ignoreId = null): array
    {
        $categories = array_keys(IspExpense::categories());
        $methods = array_keys(IspExpense::paymentMethods());
        $statuses = array_keys(IspExpense::statuses());

        return Validator::make($request->all(), [
            'category' => ['required', 'string', Rule::in($categories)],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'payment_method' => ['required', 'string', Rule::in($methods)],
            'receipt_number' => [
                'nullable',
                'string',
                'max:120',
                Rule::unique('isp_expenses', 'receipt_number')
                    ->where(fn ($query) => $query->where('isp_id', $ispId))
                    ->ignore($ignoreId),
            ],
            'expense_date' => ['required', 'date'],
            'status' => ['required', 'string', Rule::in($statuses)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'receipt_number.unique' => 'This receipt/reference is already used in this ISP expense ledger.',
        ])->validate();
    }

    private function stats(int $ispId): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $lastSevenDays = Carbon::today()->subDays(6);

        $base = $this->baseQuery($ispId)->whereIn('status', ['paid', 'reconciled']);

        return [
            'this_month' => (float) (clone $base)->whereBetween('expense_date', [$startOfMonth, $endOfMonth])->sum('amount'),
            'last_7_days' => (float) (clone $base)->whereDate('expense_date', '>=', $lastSevenDays)->sum('amount'),
            'today' => (float) (clone $base)->whereDate('expense_date', $today)->sum('amount'),
            'all_time' => (float) (clone $base)->sum('amount'),
            'records' => (int) $this->baseQuery($ispId)->count(),
        ];
    }

    private function options(): array
    {
        return [
            'categories' => collect(IspExpense::categories())->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])->values()->all(),
            'paymentMethods' => collect(IspExpense::paymentMethods())->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])->values()->all(),
            'statuses' => collect(IspExpense::statuses())->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])->values()->all(),
        ];
    }

    private function expensePayload(IspExpense $expense): array
    {
        return [
            'id' => $expense->id,
            'expense_number' => $expense->expense_number,
            'category' => $expense->category,
            'category_label' => IspExpense::categories()[$expense->category] ?? ucfirst((string) $expense->category),
            'description' => $expense->description,
            'amount' => (float) $expense->amount,
            'payment_method' => $expense->payment_method,
            'payment_method_label' => IspExpense::paymentMethods()[$expense->payment_method] ?? ucfirst((string) $expense->payment_method),
            'receipt_number' => $expense->receipt_number,
            'expense_date' => optional($expense->expense_date)->toDateString(),
            'status' => $expense->status,
            'notes' => $expense->notes,
            'edit_url' => route('expenses.edit', $expense),
            'destroy_url' => route('expenses.destroy', $expense),
        ];
    }

    private function blankExpense(): array
    {
        return [
            'id' => null,
            'expense_number' => null,
            'category' => 'other',
            'description' => '',
            'amount' => '',
            'payment_method' => 'cash',
            'receipt_number' => '',
            'expense_date' => now()->toDateString(),
            'status' => 'paid',
            'notes' => '',
        ];
    }

    private function makeExpenseNumber(): string
    {
        do {
            $number = 'EXP-' . now()->format('ymdHis') . '-' . random_int(100, 999);
        } while (IspExpense::query()->where('expense_number', $number)->exists());

        return $number;
    }

    private function authorizeExpense(Request $request, IspExpense $expense): void
    {
        $this->tenantResolver->authorize($request, (int) $expense->isp_id);
    }

    private function authorizeTenantAccess(Request $request, array $permissions): void
    {
        $user = $request->user();

        abort_unless($user, 403);

        abort_if(
            $this->tenantResolver->isPlatform($request),
            403,
            'Expenses are recorded inside a company/ISP account.'
        );

        if ($this->isCompanyOwner($user)) {
            return;
        }

        abort_unless(
            collect($permissions)->contains(fn (string $permission): bool => $user->can($permission)),
            403
        );
    }

    private function isCompanyOwner(User $user): bool
    {
        if (in_array((string) $user->type, ['company', 'admin', 'isp_admin'], true)) {
            return true;
        }

        return method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['company', 'admin', 'isp_admin']);
    }
}
