<?php

namespace App\Observers;

use App\Models\{ProductVariant, OrderProduct,CartItem};


class ProductVariantObserver
{
    /**
     * Handle the ProductVariant "created" event.
     *
     * @param  \App\Models\ProductVariant  $productVariant
     * @return void
     */
    public function created(ProductVariant $productVariant)
    {
        //
    }

    /**
     * Handle the ProductVariant "updated" event.
     *
     * @param  \App\Models\ProductVariant  $productVariant
     * @return void
     */
    public function updated(ProductVariant $productVariant)
    {
        //
    }

    /**
     * Handle the ProductVariant "deleted" event.
     *
     * @param  \App\Models\ProductVariant  $productVariant
     * @return void
     */
    public function deleted(ProductVariant $productVariant)
    {
        // CartItem::where('product_variant_id', $productVariant->id)
        //     ->whereHas('order', function ($query) {
        //         $query->where('status', 'pending');
        //     })->delete();
        CartItem::where('product_variant_id', $variant->id)->delete();
    }

    /**
     * Handle the ProductVariant "restored" event.
     *
     * @param  \App\Models\ProductVariant  $productVariant
     * @return void
     */
    public function restored(ProductVariant $productVariant)
    {
        //
    }

    /**
     * Handle the ProductVariant "force deleted" event.
     *
     * @param  \App\Models\ProductVariant  $productVariant
     * @return void
     */
    public function forceDeleted(ProductVariant $productVariant)
    {
        //
    }
}
