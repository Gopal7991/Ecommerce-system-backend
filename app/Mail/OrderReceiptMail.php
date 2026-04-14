<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content; // <--- Add this import
use Illuminate\Queue\SerializesModels;

class OrderReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $filePath; // Rename to be clear it's a string path

    public function __construct($order, $filePath)
    {
        $this->order = $order;
        $this->filePath = $filePath;
    }

    public function content()
    {
        return new Content(
            view: 'receipt',
        );
    }

    public function attachments(): array
    {
        // Use fromStorage since you saved it in the controller
        return [
            Attachment::fromStorage($this->filePath)
                ->as('Invoice-' . $this->order->id . '.pdf')
        ];
    }
}

