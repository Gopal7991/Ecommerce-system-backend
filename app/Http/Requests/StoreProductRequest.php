<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255', // Required by DB
            'category_id' => 'required|exists:categories,id', // Required by DB
            'price' => 'nullable|numeric',
            'gender' => 'nullable|string',
            'discount_price' => 'nullable|numeric',
            'sku' => 'nullable|string|unique:products,sku',
            'variants' => 'required|array',
            'variants.*.size' => 'nullable|string',
            'variants.*.color' => 'nullable|string',
            'variants.*.quantity' => 'nullable|integer',
        ];
    }
}
