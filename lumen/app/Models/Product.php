<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\CartItem;
use App\Models\OrderItem;

class Product extends Model
{
    protected $fillable = ['name', 'price', 'description', 'image', 'currency', 'email', 'phone'];

    protected $casts = [
        'price' => 'float',
        'name' => 'string',
        'description' => 'string',
        'currency' => 'string',
        'email' => 'string',
        'phone' => 'string',
    ];

    // Enforce valid email format
    public function setEmailAttribute($value)
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->attributes['email'] = strtolower($value);
        } else {
            throw new \InvalidArgumentException('Invalid email format');
        }
    }

    // Enforce valid phone format (numbers only, 10-15 digits)
    public function setPhoneAttribute($value)
    {
        // Remove all non-digit characters except the leading plus sign
        $sanitizedPhone = preg_replace('/[^\d+]/', '', $value);
    
        // Define the E.164 validation regex
        $e164Regex = '/^\+?[1-9]\d{1,14}$/';
    
        // Validate the sanitized phone number
        if (preg_match($e164Regex, $sanitizedPhone)) {
            $this->attributes['phone'] = $sanitizedPhone;
        } else {
            throw new \InvalidArgumentException('Invalid phone number format. Please provide a valid international phone number.');
        }
    }
    
    

    public function getPriceAttribute($value)
    {
        $decimalPlaces = match ($this->currency) {
            'USD', 'EUR', 'GBP', 'AUD', 'CAD' => 2,
            'BTC', 'DOGE', 'RVN' => 8,
            'ETH' => 18,
            'XMR' => 12,
            'USDT' => 6,
            'JPY' => 0,
            default => 2
        };

        return number_format((float) $value, $decimalPlaces, '.', '');
    }

    public function toArray()
    {
        $array = parent::toArray();

        $array['price'] = (string) $this->price;
        $array['currency'] = strtoupper($this->currency);
        $array['name'] = ucfirst($this->name);

        return $array;
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
