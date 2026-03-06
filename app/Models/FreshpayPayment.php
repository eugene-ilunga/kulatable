<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FreshpayPayment extends Model
{
    use HasFactory;

    protected $table = 'freshpay_payments';

    protected $fillable = [
        'freshpay_payment_id',
        'freshpay_reference',
        'freshpay_action',
        'freshpay_method',
        'customer_number',
        'financial_institution_id',
        'trans_status',
        'trans_status_description',
        'order_id',
        'amount',
        'payment_status',
        'payment_date',
        'payment_error_response',
        'callback_payload',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'payment_error_response' => 'array',
        'callback_payload' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

