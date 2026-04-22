<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['total_price', 'status', 'session_id', 'user_address_id',  'created_by', 'updated_by'];
   
    public function order_items()  {
        return $this->hasMany(OrderItem::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    public function paymentAddress()
    {
        return $this->belongsTo(UserAddress::class, 'user_address_id', 'id');
    }

    public function paymentDetails()
    {
        return $this->hasOne(Payment::class, 'order_id', 'id');
    }
    
}
