<?php

namespace App\Listeners;

use App\Events\OrderSuccess;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderPlacedMail;
use App\Models\Cart;


class SendSucessMail implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderSuccess $event)
    {
        // $userId = $event->order->user_id;

        // $cart = Cart::where('user_id', $userId)->first();
        // if ($cart) {
        //     $cart->items()->delete(); 
        //     $cart->delete(); 
        // }

        Log::info('Order confirmation sent for: ' . $event->order->email);

        Mail::to($event->order->email)->send(new OrderPlacedMail($event->order));
    }
}

