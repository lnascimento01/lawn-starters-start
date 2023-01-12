<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "payments";
    protected $fillable = [
        'user_uuid',
        'payment_method_id',
        'description',
        'value',
        'gateway_id',
        'status_id'
    ];

    public function status()
    {
        return $this->hasOne(PaymentStatus::class, 'id', 'status_id');
    }
}
