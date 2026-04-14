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
       $productId = $this->route('product') ?? $this->route('id');

        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string', 
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'price' => 'required|numeric',
            'gender' => 'nullable|string',
            'discount_price' => 'nullable|numeric',
            'sku' => [
                'required',
                'string',
                $productId ? "unique:products,sku,$productId" : "unique:products,sku"
            ],

            'has_variants' => 'required|boolean',

            'quantity' => 'required_if:has_variants,0,false|nullable|numeric',
            'variants' => 'required_if:has_variants,1,true|array|min:1',
            'variants.*.size' => 'required_with:variants|string',
            'variants.*.color' => 'required_with:variants|string',
            'variants.*.quantity' => 'required_with:variants|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'variants.required_if' => 'Please generate product variants before submit or un checked product has variants checkbox.',
                        'variants.min' => 'At least one size and color combination is required.',
            'quantity.required_if' => 'Standard product quantity is required when variants are disabled.',
            
            'variants.*.quantity.required_with' => 'Every variant must have a defined stock quantity.',
        ];
    }
}
