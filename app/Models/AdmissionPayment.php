<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class AdmissionPayment extends Model
{
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Payment Status
    |--------------------------------------------------------------------------
    */

    public const STATUS_PENDING   = 'pending';
    public const STATUS_PAID      = 'paid';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED  = 'refunded';

    protected $fillable = [
        'student_id',
        'admission_id',
        'user_id',
        'amount',
        'paid_at',
        'payment_method',
        'status',
        'receipt_number',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function collectedBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Status Helpers
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', self::STATUS_REFUNDED);
    }

    /*
    |--------------------------------------------------------------------------
    | Booted
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::creating(function ($payment) {

            /*
            |--------------------------------------------------------------------------
            | Receipt Number Generate
            |--------------------------------------------------------------------------
            */

            if (empty($payment->receipt_number)) {

                $date = now()->format('Ymd');

                $countToday = self::whereDate('created_at', today())->count() + 1;

                $payment->receipt_number =
                    'REC-NO-' .
                    $date .
                    '-' .
                    str_pad($countToday, 3, '0', STR_PAD_LEFT);
            }

            /*
            |--------------------------------------------------------------------------
            | Default Note
            |--------------------------------------------------------------------------
            */

            if (empty($payment->note)) {

                $payment->note =
                    'Admission payment collected successfully.';
            }

            /*
            |--------------------------------------------------------------------------
            | Default User
            |--------------------------------------------------------------------------
            */

            if (empty($payment->user_id)) {

                $payment->user_id = Auth::id();
            }

            /*
            |--------------------------------------------------------------------------
            | Default Payment Method
            |--------------------------------------------------------------------------
            */

            if (empty($payment->payment_method)) {

                $payment->payment_method = 'cash';
            }

            /*
            |--------------------------------------------------------------------------
            | Default Status
            |--------------------------------------------------------------------------
            */

            if (empty($payment->status)) {

                $payment->status = self::STATUS_PAID;
            }

            /*
            |--------------------------------------------------------------------------
            | Default Paid Date
            |--------------------------------------------------------------------------
            */

            if (empty($payment->paid_at)) {

                $payment->paid_at = now();
            }
        });
    }
}