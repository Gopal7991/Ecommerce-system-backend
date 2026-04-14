<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'amount' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'couponcode' => 'nullable|string|exists:coupons,code',
            'discountproduct' => 'nullable|array',
            
            'shipping' => 'required|array',
            'shipping.name' => 'required|string|max:255',
            'shipping.email' => 'required|email',
            'shipping.address' => 'required|string',
            'shipping.city' => 'required|string',
            'shipping.state' => 'required|string',
            'shipping.zip' => 'required|digits:6',
            'shipping.phone' => 'required|digits:10',

            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|integer',
            'items.*.name' => 'required|string',
            'items.*.price' => 'required|numeric',
            'items.*.quantity' => 'required|integer|min:1',
        ];
    }
}
