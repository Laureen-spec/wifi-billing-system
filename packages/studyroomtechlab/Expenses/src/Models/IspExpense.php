<?php

namespace StudyRoomTechLab\Expenses\Models;

use App\Models\Isp;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IspExpense extends Model
{
    use SoftDeletes;

    protected $table = 'isp_expenses';

    protected $fillable = [
        'isp_id',
        'expense_number',
        'category',
        'description',
        'amount',
        'payment_method',
        'receipt_number',
        'expense_date',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    public function isp(): BelongsTo
    {
        return $this->belongsTo(Isp::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function categories(): array
    {
        return [
            'bandwidth' => 'Bandwidth / Uplink',
            'hardware' => 'Hardware',
            'router_maintenance' => 'Router Maintenance',
            'salaries' => 'Salaries',
            'transport' => 'Transport',
            'marketing' => 'Marketing',
            'rent' => 'Rent',
            'electricity' => 'Electricity',
            'utilities' => 'Utilities',
            'other' => 'Other',
        ];
    }

    public static function paymentMethods(): array
    {
        return [
            'cash' => 'Cash',
            'mpesa' => 'M-Pesa',
            'bank' => 'Bank',
            'card' => 'Card',
            'other' => 'Other',
        ];
    }

    public static function statuses(): array
    {
        return [
            'paid' => 'Paid',
            'pending' => 'Pending',
            'reconciled' => 'Reconciled',
        ];
    }
}
