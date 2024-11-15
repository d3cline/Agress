<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\OrderItem;

class Order extends Model
{
    protected $fillable = [
        'fullName',
        'email',
        'phoneNumber',
        'shippingAddress',
        'city',
        'postalCode',
        'cart',
        'status'
    ];

    protected $casts = [
        'cart' => 'array',
    ];

    // Sanitize and validate email
    public function setEmailAttribute($value)
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->attributes['email'] = strtolower(trim($value));
        } else {
            throw new \InvalidArgumentException('Invalid email format');
        }
    }

    // Sanitize and validate phone number
    public function setPhoneNumberAttribute($value)
    {
        $sanitizedPhone = preg_replace('/\D/', '', $value);
        if (preg_match('/^\d{10,15}$/', $sanitizedPhone)) {
            $this->attributes['phoneNumber'] = $sanitizedPhone;
        } else {
            throw new \InvalidArgumentException('Invalid phone number format');
        }
    }

    // Sanitize full name
    public function setFullNameAttribute($value)
    {
        $this->attributes['fullName'] = ucwords(strtolower(trim($value)));
    }

    // Sanitize shipping address
    public function setShippingAddressAttribute($value)
    {
        $this->attributes['shippingAddress'] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    // Sanitize city
    public function setCityAttribute($value)
    {
        $this->attributes['city'] = ucwords(strtolower(trim($value)));
    }

    // Sanitize postal code (numbers and uppercase letters only)
    public function setPostalCodeAttribute($value)
    {
        $sanitizedPostalCode = strtoupper(preg_replace('/[^A-Z0-9]/', '', $value));
        $this->attributes['postalCode'] = $sanitizedPostalCode;
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
