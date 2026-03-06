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
            'price' => 'required|numeric',
            'gender' => 'nullable|string',
            'discount_price' => 'nullable|numeric',
            'quantity' => 'nullable|numeric',
            'sku' => [
                'required',
                'string',
                $productId ? "unique:products,sku,$productId" : "unique:products,sku"
            ],
            'variants' => 'nullable|array',
            'variants.*.size' => 'nullable|string',
            'variants.*.color' => 'nullable|string',
            'variants.*.quantity' => 'nullable|integer',
        ];
    }
}
