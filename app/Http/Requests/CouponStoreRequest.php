<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CouponStoreRequest extends FormRequest
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
        // $couponId = $this->route('coupon') ?? $this->route('id');

        return [
            'name' => 'required|string|max:255',
            'code' => 'required|unique:coupons,code,' . $this->route('id'),
            'coupon_type' => 'required|string',
            'brand_id' => 'nullable|exists:brands,id',
            'discount_percentage' => 'nullable|numeric',
            'start_date' => 'required|date|date_format:Y-m-d',
            'end_date' => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
            'min_order_amount' => 'required|numeric',
            'max_discount' => 'required|numeric',
            'max_attach' => 'required|numeric',
            'is_active' => 'nullable'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'is_active' => $this->boolean('is_active') ? 1 : 0,
        ]);
    }
}
