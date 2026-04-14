<?php

namespace App\Listeners;

use App\Events\OrderInvoice;
use App\Mail\OrderReceiptMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendInvoiceListener implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\OrderInvoice  $event
     * @return void
     */
    public function handle(OrderInvoice $event)
    {
        // echo "<pre>"; print_r($event);exit;

        Mail::to($event->order->email)->send(new OrderReceiptMail($event->order,$event->file));
    }
}
